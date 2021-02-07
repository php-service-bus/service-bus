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

use ServiceBus\Context\ContextFactory;
use ServiceBus\Metadata\ServiceBusMetadata;
use function Amp\call;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\MessageSerializer\Exceptions\DecodeMessageFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function ServiceBus\Common\throwableDetails;

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
     * @param IncomingMessageDecoder $messageDecoder
     * @param ContextFactory         $contextFactory
     * @param Router                 $messagesRouter
     * @param LoggerInterface        $logger
     */
    public function __construct(
        IncomingMessageDecoder $messageDecoder,
        ContextFactory $contextFactory,
        ?Router $messagesRouter = null,
        ?LoggerInterface $logger = null
    ) {
        $this->messageDecoder = $messageDecoder;
        $this->contextFactory = $contextFactory;
        $this->messagesRouter = $messagesRouter ?? new Router();
        $this->logger         = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(IncomingPackage $package): Promise
    {
        return call(
            function () use ($package): \Generator
            {
                [$headers, $metadataVariables] = self::splitHeaders($package);

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
                                'traceId'   => $metadata->get(ServiceBusMetadata::SERVICE_BUS_TRACE_ID),
                                'payload'   => $package->payload(),
                            ]
                        )
                    );

                    yield $package->ack();

                    return;
                }

                $executors = $this->messagesRouter->match($message);

                if (\count($executors) === 0)
                {
                    $this->logger->debug(
                        'There are no handlers configured for the message "{messageClass}"',
                        [
                            'messageClass' => \get_class($message),
                            'traceId'      => $metadata->get(ServiceBusMetadata::SERVICE_BUS_TRACE_ID),
                        ]
                    );

                    yield $package->ack();

                    return;
                }

                $context = $this->contextFactory->create(
                    message: $message,
                    headers: $headers,
                    metadata: $metadata
                );

                /** @var \ServiceBus\Common\MessageExecutor\MessageExecutor $executor */
                foreach ($executors as $executor)
                {
                    try
                    {
                        yield $executor($message, $context);
                    }
                    catch (\Throwable $throwable)
                    {
                        $context->logger()->throwable($throwable);
                    }
                }

                yield $package->ack();
            }
        );
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
}
