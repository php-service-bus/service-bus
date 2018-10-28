<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\EntryPoint;

use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Endpoint\EndpointRouter;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\IncomingMessageDecoder;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Queue;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Desperado\ServiceBus\MessageRouter\Router;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 *
 */
final class EntryPoint
{
    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var Router
     */
    private $messagesRouter;

    /**
     * @var Queue|null
     */
    private $listenQueue;

    /**
     * Decoding of incoming messages
     *
     * @var IncomingMessageDecoder
     */
    private $messageDecoder;

    /**
     * Outbound message routing
     *
     * @var EndpointRouter
     */
    private $endpointRouter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Transport              $transport
     * @param IncomingMessageDecoder $messageDecoder
     * @param EndpointRouter         $endpointRouter
     * @param Router|null            $messagesRouter
     * @param LoggerInterface|null   $logger
     */
    public function __construct(
        Transport $transport,
        IncomingMessageDecoder $messageDecoder,
        EndpointRouter $endpointRouter,
        ?Router $messagesRouter = null,
        ?LoggerInterface $logger = null
    )
    {
        $this->logger = $logger ?? new NullLogger();

        $this->transport = $transport;
        $this->messageDecoder = $messageDecoder;
        $this->endpointRouter = $endpointRouter;
        $this->messagesRouter = $messagesRouter ?? new Router();
    }

    /**
     * Start queue listen
     *
     * @param Queue $queue
     *
     * @return Promise It does not return any result
     */
    public function listen(Queue $queue): Promise
    {
        $this->listenQueue = $queue;

        $transport = $this->transport;
        $logger = $this->logger;
        $decoder = $this->messageDecoder;
        $router = $this->messagesRouter;
        $endpointRouter = $this->endpointRouter;

        /** Hack for phpunit tests */
        $isTestCall = 'phpunitTests' === (string) \getenv('SERVICE_BUS_TESTING');

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Queue $queue) use ($transport, $decoder, $router, $logger, $endpointRouter, $isTestCall): \Generator
            {
                /** @var \Amp\Iterator $iterator */
                $iterator = yield $transport->consume($queue);

                while(yield $iterator->advance())
                {
                    /** @var IncomingPackage $package */
                    $package = $iterator->getCurrent();

                    try
                    {
                        /** @var \Desperado\ServiceBus\Common\Contract\Messages\Message $message */
                        $message = yield $decoder->decode($package);

                        $logger->debug('Dispatch "{messageClass}" message', [
                            'packageId'    => $package->id(),
                            'traceId'      => $package->traceId(),
                            'messageClass' => \get_class($message)
                        ]);

                        $context = new KernelContext($package, $endpointRouter, $logger);

                        $logger->debug('Handle message "{messageClass}"', [
                                'messageClass' => \get_class($message),
                                'packageId'    => $package->id(),
                                'traceId'      => $package->traceId(),
                            ]
                        );

                        yield $package->ack();
                        yield self::process($router, $message, $context);

                        unset($message, $context);
                    }
                    catch(\Throwable $throwable)
                    {
                        $throwable instanceof DecodeMessageFailed
                            ? yield $package->reject(false)
                            : yield $package->reject(true);

                        $logger->critical($throwable->getMessage(), [
                            'packageId'      => $package->id(),
                            'traceId'        => $package->traceId(),
                            'throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                        ]);
                    }

                    unset($package);

                    /** Hack for phpunit tests */
                    if(true === $isTestCall)
                    {
                        break;
                    }
                }
            },
            $queue
        );
    }

    /**
     * Process message execution
     *
     * @param Router        $router
     * @param Message       $message
     * @param KernelContext $context
     *
     * @return Promise It does not return any result
     */
    private static function process(Router $router, Message $message, KernelContext $context): Promise
    {
        $deferred = new Deferred();

        Loop::defer(
            static function() use ($router, $deferred, $message, $context): \Generator
            {
                try
                {
                    $executors = $router->match($message);
                    $messageClass = \get_class($message);

                    if(0 === \count($executors))
                    {
                        $context->logContextMessage(
                            'There are no handlers configured for the message "{messageClass}"',
                            ['messageClass' => $messageClass], LogLevel::DEBUG
                        );

                        $deferred->resolve();

                        return;
                    }

                    foreach($executors as $executor)
                    {
                        /**
                         * @see \Desperado\ServiceBus\MessageExecutor\MessageExecutor::__invoke
                         *
                         * @var callable $executor
                         */

                        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
                        yield call($executor, $message, $context);
                    }

                    unset($executors, $messageClass);

                    $deferred->resolve();
                }
                catch(\Throwable $throwable)
                {
                    $deferred->fail($throwable);
                }
            }
        );

        return $deferred->promise();
    }

    /**
     * @param int $delay The delay before the completion (in seconds)
     *
     * @return void
     */
    public function stop(int $delay = 10): void
    {
        $delay = 0 >= $delay ? 1 : $delay;

        Loop::defer(
            function() use ($delay): \Generator
            {
                if(null === $this->listenQueue)
                {
                    Loop::stop();

                    return;
                }

                yield $this->transport->stop($this->listenQueue);

                $this->logger->info('Handler will stop after {duration} seconds', ['duration' => $delay]);

                Loop::delay(
                    $delay * 1000,
                    function(): void
                    {
                        $this->logger->info('The event loop has been stopped');

                        Loop::stop();
                    }
                );
            }
        );
    }
}
