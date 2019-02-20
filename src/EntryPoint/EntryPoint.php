<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\EntryPoint;

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
 * Application entry point
 */
final class EntryPoint
{
    private const DEFAULT_MAX_CONCURRENT_TASK_COUNT = 60;
    private const DEFAULT_AWAIT_DELAY               = 20;

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
     * @param Transport            $transport
     * @param  EntryPointProcessor $processor
     * @param LoggerInterface|null $logger
     * @param int|null             $maxConcurrentTaskCount
     * @param int|null             $awaitDelay Barrier wait delay (in milliseconds)
     */
    public function __construct(
        Transport $transport,
        EntryPointProcessor $processor,
        ?LoggerInterface $logger = null,
        ?int $maxConcurrentTaskCount = null,
        ?int $awaitDelay = null
    )
    {
        $this->transport              = $transport;
        $this->processor              = $processor;
        $this->logger                 = $logger ?? new NullLogger();
        $this->maxConcurrentTaskCount = $maxConcurrentTaskCount ?? self::DEFAULT_MAX_CONCURRENT_TASK_COUNT;
        $this->awaitDelay             = $awaitDelay ?? self::DEFAULT_AWAIT_DELAY;
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

        /** Hack for phpunit tests */
        $isTestCall = 'phpunitTests' === (string) \getenv('SERVICE_BUS_TESTING');

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Queue $queue) use ($isTestCall): \Generator
            {
                /**
                 * @psalm-suppress TooManyTemplateParams Wrong Iterator template
                 * @var \Amp\Iterator $iterator
                 */
                $iterator = yield $this->transport->consume($queue);

                /** @psalm-suppress TooManyTemplateParams Wrong Promise template */
                while(yield $iterator->advance())
                {
                    $this->currentTasksInProgressCount++;

                    /** @var IncomingPackage $package */
                    $package = $iterator->getCurrent();

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

                    while($this->maxConcurrentTaskCount <= $this->currentTasksInProgressCount)
                    {
                        yield new Delayed($this->awaitDelay);
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
