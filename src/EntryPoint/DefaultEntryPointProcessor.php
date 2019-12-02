<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\EntryPoint;

use ServiceBus\Context\ContextFactory;
use function Amp\call;
use function ServiceBus\Common\collectThrowableDetails;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\MessageSerializer\Exceptions\DecodeMessageFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 * Default incoming package processor.
 */
final class DefaultEntryPointProcessor implements EntryPointProcessor
{
    private IncomingMessageDecoder $messageDecoder;
    private ContextFactory         $contextFactory;
    private Router                 $messagesRouter;
    private LoggerInterface        $logger;

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
        $messageDecoder = $this->messageDecoder;
        $messagesRouter = $this->messagesRouter;
        $logger         = $this->logger;
        $contextFactory = $this->contextFactory;

        return call(
            static function (IncomingPackage $package) use ($messageDecoder, $messagesRouter, $contextFactory, $logger): \Generator
            {
                try
                {
                    $message = $messageDecoder->decode($package);
                }
                catch (DecodeMessageFailed $exception)
                {
                    $logger->error(
                        'Failed to denormalize the message',
                        \array_merge(
                            collectThrowableDetails($exception),
                            [
                                'packageId' => $package->id(),
                                'traceId'   => $package->traceId(),
                                'payload'   => $package->payload(),
                            ]
                        )
                    );

                    yield $package->ack();

                    return;
                }

                $logger->debug('Dispatch "{messageClass}" message', [
                    'packageId'    => $package->id(),
                    'traceId'      => $package->traceId(),
                    'messageClass' => \get_class($message),
                ]);

                $executors = $messagesRouter->match($message);

                if (0 === \count($executors))
                {
                    $logger->debug(
                        'There are no handlers configured for the message "{messageClass}"',
                        ['messageClass' => \get_class($message)]
                    );
                }

                $context = $contextFactory->create($package, $message);

                /** @var \ServiceBus\Common\MessageExecutor\MessageExecutor $executor */
                foreach ($executors as $executor)
                {
                    try
                    {
                        yield $executor($message, $context);
                    }
                    catch (\Throwable $throwable)
                    {
                        $context->logContextThrowable($throwable);
                    }
                }

                unset($context);

                yield $package->ack();
            },
            $package
        );
    }
}
