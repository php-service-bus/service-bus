<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\EntryPoint;

use function Amp\call;
use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\Transport;

/**
 *
 */
final class EntryPoint
{
    private const DEFAULT_MAX_CONCURRENT_TASK_COUNT = 50;
    private const DEFAULT_AWAIT_DELAY               = 50;

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
     * The max number of concurrent tasks
     *
     * @var int
     */
    private $maxConcurrentTaskCount;

    /**
     * The current number of tasks performed
     *
     * @var int
     */
    private $currentTasksInProgressCount = 0;

    /**
     * Barrier wait delay (in milliseconds)
     *
     * @var int
     */
    private $awaitDelay;

    /**
     * @param  EntryPointProcessor $processor
     * @param LoggerInterface|null $logger
     * @param int|null             $maxConcurrentTaskCount
     * @param int|null             $awaitDelay Barrier wait delay (in milliseconds)
     */
    public function __construct(
        EntryPointProcessor $processor,
        ?LoggerInterface $logger = null,
        ?int $maxConcurrentTaskCount = null,
        ?int $awaitDelay = null
    )
    {
        $this->processor              = $processor;
        $this->logger                 = $logger ?? new NullLogger();
        $this->maxConcurrentTaskCount = $maxConcurrentTaskCount ?? self::DEFAULT_MAX_CONCURRENT_TASK_COUNT;
        $this->awaitDelay             = $awaitDelay ?? self::DEFAULT_AWAIT_DELAY;
    }

    /**
     * Start queue listen
     *
     * @param Transport $transport
     * @param Queue     $queue
     *
     * @return Promise It does not return any result
     */
    public function listen(Transport $transport, Queue $queue): Promise
    {
        $this->listenQueue = $queue;

        /** Hack for phpunit tests */
        $isTestCall = 'phpunitTests' === (string) \getenv('SERVICE_BUS_TESTING');

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Transport $transport, Queue $queue) use ($isTestCall): \Generator
            {
                /** @var \Amp\Iterator $iterator */
                $iterator = yield $transport->consume($queue);

                while(yield $iterator->advance())
                {
                    /** @var IncomingPackage $package */
                    $package = $iterator->getCurrent();

                    while($this->maxConcurrentTaskCount <= $this->currentTasksInProgressCount)
                    {
                        yield new Delayed($this->awaitDelay);
                    }

                    $this->currentTasksInProgressCount++;

                    /** Hack for phpUnit */
                    if(true === $isTestCall)
                    {
                        yield $this->processor->handle($package);

                        break;
                    }

                    $this->processor->handle($package)->onResolve(
                        function(?\Throwable $throwable) use ($package): void
                        {
                            $this->currentTasksInProgressCount--;

                            if(null === $throwable)
                            {
                                return;
                            }

                            $this->logger->critical($throwable->getMessage(), [
                                'packageId'      => $package->id(),
                                'traceId'        => $package->traceId(),
                                'throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                            ]);
                        }
                    );
                }
            },
            $transport, $queue
        );
    }

    /**
     * @param Transport $transport
     * @param int       $delay The delay before the completion (in seconds)
     *
     * @return void
     */
    public function stop(Transport $transport, int $delay = 10): void
    {
        $delay = 0 >= $delay ? 1 : $delay;

        Loop::defer(
            function() use ($transport, $delay): \Generator
            {
                if(null === $this->listenQueue)
                {
                    Loop::stop();

                    return;
                }

                yield $transport->stop($this->listenQueue);

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
