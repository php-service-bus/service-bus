<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\EntryPoint;

use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\EntryPoint\Retry\FailureContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Common\Metadata\ServiceBusMetadata;
use ServiceBus\Context\ContextFactory;
use ServiceBus\Retry\NullRetryStrategy;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\MessageSerializer\Exceptions\DecodeObjectFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\call;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\throwableMessage;

/**
 * Default incoming package processor.
 */
final class DefaultEntryPointProcessor implements EntryPointProcessor
{
    /**
     * @var IncomingMessageDecoder
     */
    private $messageDecoder;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @var Router
     */
    private $messagesRouter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RetryStrategy
     */
    private $retryStrategy;

    public function __construct(
        IncomingMessageDecoder $messageDecoder,
        ContextFactory         $contextFactory,
        ?RetryStrategy         $retryStrategy = null,
        ?Router                $messagesRouter = null,
        ?LoggerInterface       $logger = null
    ) {
        $this->messageDecoder = $messageDecoder;
        $this->contextFactory = $contextFactory;
        $this->retryStrategy  = $retryStrategy ?? new NullRetryStrategy();
        $this->messagesRouter = $messagesRouter ?? new Router();
        $this->logger         = $logger ?? new NullLogger();
    }

    public function handle(IncomingPackage $package): Promise
    {
        return call(
            function () use ($package): \Generator
            {
                $messageInfo = $this->collectMessageInfo($package);

                if ($messageInfo === null)
                {
                    yield $package->ack();

                    return;
                }

                $context = $this->contextFactory->create(
                    message: $messageInfo['message'],
                    headers: $messageInfo['headers'],
                    metadata: $messageInfo['metadata']
                );

                $executors = $this->collectExecutors(
                    message: $messageInfo['message'],
                    traceId: $package->traceId(),
                    filterByRecipient: self::isRetrying($messageInfo['metadata'])
                        ? self::failedInContext($messageInfo['metadata'])
                        : []
                );

                if ($executors === null)
                {
                    yield $package->ack();

                    return;
                }

                $globalRetryQueue = [];

                foreach ($executors as $executor)
                {
                    try
                    {
                        /** @var \Throwable|null $result */
                        $result = yield $executor($messageInfo['message'], $context);

                        if ($result instanceof \Throwable)
                        {
                            throw $result;
                        }
                    }
                    catch (\Throwable $throwable)
                    {
                        $context->logger()->throwable($throwable);

                        $handlerRetryStrategy = $executor->retryStrategy();

                        if ($handlerRetryStrategy !== null)
                        {
                            yield $handlerRetryStrategy->retry(
                                message: $messageInfo['message'],
                                context: $context,
                                details: new FailureContext([$executor->id() => throwableMessage($throwable)])
                            );
                        }
                        else
                        {
                            $globalRetryQueue[$executor->id()] = throwableMessage($throwable);
                        }
                    }
                }

                if (\count($globalRetryQueue) !== 0)
                {
                    yield $this->retryStrategy->retry(
                        message: $messageInfo['message'],
                        context: $context,
                        details: new FailureContext($globalRetryQueue)
                    );
                }

                yield $package->ack();
            }
        );
    }

    /**
     * The first step is to get a general list of handlers that fit this message.
     * If this is a repeated execution attempt, then we will try to execute the message only in those handlers in
     * which the processing ended with an error.
     * If not, return all handlers.
     *
     * @psalm-param non-empty-string       $traceId
     * @psalm-param list<non-empty-string> $filterByRecipient
     *
     * @psalm-return non-empty-array<array-key, \ServiceBus\Common\MessageExecutor\MessageExecutor>|null
     */
    private function collectExecutors(
        object $message,
        string $traceId,
        array  $filterByRecipient = []
    ): ?array {
        $executors = $this->messagesRouter->match($message);

        if (\count($executors) === 0)
        {
            $this->logger->debug(
                'There are no handlers configured for the message "{messageClass}"',
                [
                    'messageClass' => \get_class($message),
                    'traceId'      => $traceId,
                ]
            );

            return null;
        }

        /** In case of reprocessing */
        if (!empty($filterByRecipient))
        {
            /** @psalm-var list<MessageExecutor> $specificHandlers */
            $specificHandlers = \array_filter(
                \array_map(
                    static function (MessageExecutor $messageExecutor) use ($filterByRecipient): ?MessageExecutor
                    {
                        return \in_array($messageExecutor->id(), $filterByRecipient, true)
                            ? $messageExecutor
                            : null;
                    },
                    $executors
                )
            );

            if (\count($specificHandlers) !== 0)
            {
                return $specificHandlers;
            }

            return null;
        }

        return $executors;
    }

    /**
     * @psalm-return array{
     *     message:object,
     *     headers:array<non-empty-string, int|float|string|null>,
     *     metadata:ReceivedMessageMetadata
     * }|null
     */
    private function collectMessageInfo(IncomingPackage $package): ?array
    {
        $typedHeaders = $this->splitHeaders($package);

        $metadata = new ReceivedMessageMetadata(
            messageId: $package->id(),
            traceId: $package->traceId(),
            variables: $typedHeaders['metadata']
        );

        try
        {
            $message = $this->messageDecoder->decode(
                payload: $package->payload(),
                metadata: $metadata
            );
        }
        catch (DecodeObjectFailed $exception)
        {
            $this->logger->error(
                'Failed to denormalize the message',
                \array_merge(
                    throwableDetails($exception),
                    [
                        'packageId' => $package->id(),
                        'traceId'   => $package->traceId(),
                        'payload'   => $package->payload(),
                    ]
                )
            );

            return null;
        }

        return [
            'message'  => $message,
            'headers'  => $typedHeaders['headers'],
            'metadata' => $metadata
        ];
    }

    /**
     * @psalm-return array{
     *     headers:array<non-empty-string, int|float|string|null>,
     *     metadata:array<non-empty-string, string|int|float|bool|null>
     * }
     */
    private function splitHeaders(IncomingPackage $package): array
    {
        $headers = $package->headers();

        $metadataVariables = [];

        foreach (ServiceBusMetadata::INTERNAL_METADATA_KEYS as $metadataHeader)
        {
            if (\array_key_exists($metadataHeader, $headers))
            {
                $metadataVariables[$metadataHeader] = $headers[$metadataHeader];

                unset($headers[$metadataHeader]);
            }
        }

        return [
            'headers'  => $headers,
            'metadata' => $metadataVariables
        ];
    }

    /**
     * Was the received message sent for retry?
     */
    private static function isRetrying(IncomingMessageMetadata $metadata): bool
    {
        return !empty($metadata->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT));
    }

    /**
     * Handlers in which message processing was completed with an error.
     *
     * @psalm-return list<non-empty-string>
     */
    private static function failedInContext(IncomingMessageMetadata $metadata): array
    {
        $value = (string) ($metadata->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_FAILED_IN, ''));

        /**
         * @psalm-var list<non-empty-string> $messageExecutorIds
         */
        $messageExecutorIds = \array_filter(
            \array_map(
                static function (string $each): ?string
                {
                    return $each !== '' ? $each : null;
                },
                \explode(',', $value)
            )
        );

        return $messageExecutorIds;
    }
}
