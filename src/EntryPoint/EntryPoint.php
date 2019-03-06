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
 * Application entry point.
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
     * @var EntryPointProcessor
     */
    private $processor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The max number of concurrent tasks.
     *
     * @var int
     */
    private $maxConcurrentTaskCount;

    /**
     * The current number of tasks performed.
     *
     * @var int
     */
    private $currentTasksInProgressCount = 0;

    /**
     * Barrier wait delay (in milliseconds).
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
    ) {
        $this->transport              = $transport;
        $this->processor              = $processor;
        $this->logger                 = $logger ?? new NullLogger();
        $this->maxConcurrentTaskCount = $maxConcurrentTaskCount ?? self::DEFAULT_MAX_CONCURRENT_TASK_COUNT;
        $this->awaitDelay             = $awaitDelay ?? self::DEFAULT_AWAIT_DELAY;
    }

    /**
     * Start queue listen.
     *
     * @param Queue ...$queues
     *
     * @return Promise It does not return any result
     */
    public function listen(Queue ...$queues): Promise
    {
        /** Hack for phpunit tests */
        $isTestCall = 'phpunitTests' === (string) \getenv('SERVICE_BUS_TESTING');

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(array $queues) use ($isTestCall): \Generator
            {
                /**
                 * @psalm-suppress TooManyTemplateParams Wrong Iterator template
                 *
                 * @var Queue[] $queues
                 * @var \Amp\Iterator $iterator
                 */
                $iterator = yield $this->transport->consume(...$queues);

                /** @psalm-suppress TooManyTemplateParams Wrong Promise template */
                while (yield $iterator->advance())
                {
                    $this->currentTasksInProgressCount++;

                    /** @var IncomingPackage $package */
                    $package = $iterator->getCurrent();

                    /** Hack for phpUnit */
                    if (true === $isTestCall)
                    {
                        $this->currentTasksInProgressCount--;

                        yield $this->processor->handle($package);

                        break;
                    }

                    /** Handle incoming package */
                    $this->deferExecution($package);

                    /** Limit the maximum number of concurrently running tasks */
                    while ($this->maxConcurrentTaskCount <= $this->currentTasksInProgressCount)
                    {
                        yield new Delayed($this->awaitDelay);
                    }
                }
            },
            $queues
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

        /** @psalm-suppress MixedTypeCoercion Incorrect amphp types */
        Loop::defer(
            function() use ($delay): \Generator
            {
                yield $this->transport->stop();

                $this->logger->info('Handler will stop after {duration} seconds', ['duration' => $delay]);

                /** @psalm-suppress MixedTypeCoercion Incorrect amphp types */
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

    /**
     * @param IncomingPackage $package
     *
     * @return void
     */
    private function deferExecution(IncomingPackage $package): void
    {
        /** @psalm-suppress MixedTypeCoercion Incorrect amphp types */
        Loop::defer(
            function() use ($package): \Generator
            {
                try
                {
                    yield $this->processor->handle($package);

                    $this->currentTasksInProgressCount--;
                }
                catch (\Throwable $throwable)
                {
                    $this->logger->critical($throwable->getMessage(), [
                        'packageId'      => $package->id(),
                        'traceId'        => $package->traceId(),
                        'throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine()),
                    ]);
                }
            }
        );
    }
}
