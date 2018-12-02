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
use Amp\Coroutine;
use Amp\Loop;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Queue;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Psr\Log\LoggerInterface;
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
     * @var Queue|null
     */
    private $listenQueue;

    /**
     * @var EntryPointProcessor
     */
    private $processor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Transport            $transport
     * @param  EntryPointProcessor $processor
     * @param LoggerInterface|null $logger
     */
    public function __construct(Transport $transport, EntryPointProcessor $processor, ?LoggerInterface $logger = null)
    {
        $this->transport = $transport;
        $this->processor = $processor;
        $this->logger = $logger ?? new NullLogger();
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
        $processor = $this->processor;

        /** Hack for phpunit tests */
        $isTestCall = 'phpunitTests' === (string) \getenv('SERVICE_BUS_TESTING');

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Queue $queue) use ($transport, $processor, $logger, $isTestCall): \Generator
            {
                /** @var \Amp\Iterator $iterator */
                $iterator = yield $transport->consume($queue);

                while(yield $iterator->advance())
                {
                    /** @var IncomingPackage $package */
                    $package = $iterator->getCurrent();

                    try
                    {
                        yield (new Coroutine($processor->handle($package)));
                    }
                    catch(\Throwable $throwable)
                    {
                        $logger->critical($throwable->getMessage(), [
                            'packageId'      => $package->id(),
                            'traceId'        => $package->traceId(),
                            'throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                        ]);
                    }

                    /** Hack for phpUnit */
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
