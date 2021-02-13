<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\EntryPoint;

use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\EntryPoint\Retry\FailureContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Context\ContextFactory;
use ServiceBus\Metadata\ServiceBusMetadata;
use ServiceBus\Retry\NullRetryStrategy;
use function Amp\call;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\MessageSerializer\Exceptions\DecodeMessageFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Transport\Common\Package\IncomingPackage;
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

    /**
     * @param IncomingMessageDecoder $messageDecoder
     * @param ContextFactory         $contextFactory
     * @param Router                 $messagesRouter
     * @param LoggerInterface        $logger
     */
    public function __construct(
        IncomingMessageDecoder $messageDecoder,
        ContextFactory $contextFactory,
        ?RetryStrategy $retryStrategy = null,
        ?Router $messagesRouter = null,
        ?LoggerInterface $logger = null
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

                /**
                 * @psalm-var object                               $message
                 * @psalm-var array<string, int|float|string|null> $headers
                 * @psalm-var IncomingMessageMetadata              $metadata
                 */
                [$message, $headers, $metadata] = $messageInfo;

                $context = $this->contextFactory->create(
                    message: $message,
                    headers: $headers,
                    metadata: $metadata
                );

                $executors = $this->collectExecutors(
                    message: $message,
                    metadata: $metadata,
                    filterByRecipient: self::isRetrying($metadata) ? self::failedInContext($metadata) : []
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
                        $result = yield $executor($message, $context);

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
                                message: $message,
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

                if (!empty($globalRetryQueue))
                {
                    yield $this->retryStrategy->retry(
                        message: $message,
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
     * @param string[] $filterByRecipient
     *
     * @return MessageExecutor[]
     */
    private function collectExecutors(
        object $message,
        IncomingMessageMetadata $metadata,
        array $filterByRecipient = []
    ): ?array {
        $executors = $this->messagesRouter->match($message);

        if (\count($executors) === 0)
        {
            $this->logger->debug(
                'There are no handlers configured for the message "{messageClass}"',
                [
                    'messageClass' => \get_class($message),
                    'traceId'      => self::traceId($metadata),
                ]
            );

            return null;
        }

        if (!empty($filterByRecipient))
        {
            return \array_filter(
                \array_map(
                    static function (MessageExecutor $messageExecutor) use ($filterByRecipient)
                    {
                        return \in_array($messageExecutor->id(), $filterByRecipient, true)
                            ? $messageExecutor
                            : null;
                    },
                    $executors
                )
            );
        }

        return $executors;
    }

    private function collectMessageInfo(IncomingPackage $package): ?array
    {
        [$headers, $metadataVariables] = $this->splitHeaders($package);

        $metadata = ReceivedMessageMetadata::create($package->id(), $metadataVariables);

        try
        {
            $message = $this->messageDecoder->decode(
                payload: $package->payload(),
                metadata: $metadata
            );
        }
        catch (DecodeMessageFailed $exception)
        {
            $this->logger->error(
                'Failed to denormalize the message',
                \array_merge(
                    throwableDetails($exception),
                    [
                        'packageId' => $package->id(),
                        'traceId'   => self::traceId($metadata),
                        'payload'   => $package->payload(),
                    ]
                )
            );

            return null;
        }

        return [$message, $headers, $metadata];
    }

    /**
     * @psalm-return array<int, array<string, int|float|string|null>>
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

        return [$headers, $metadataVariables];
    }

    private static function traceId(IncomingMessageMetadata $metadata): ?string
    {
        $traceId = $metadata->get(ServiceBusMetadata::SERVICE_BUS_TRACE_ID);

        return $traceId !== null ? (string) $traceId : null;
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
     * @return string[]
     */
    private static function failedInContext(IncomingMessageMetadata $metadata): array
    {
        $value = (string) ($metadata->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_FAILED_IN, ''));

        return \explode(',', $value);
    }
}
