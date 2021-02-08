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

use Amp\CancelledException;
use Amp\TimeoutException;
use function Amp\delay;
use Amp\Loop;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\Transport;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\throwableMessage;

/**
 * Application entry point.
 * It is the entry point for messages coming from a transport. Responsible for processing.
 */
final class EntryPoint
{
    /**
     * The default value for the maximum number of tasks processed simultaneously.
     * The value should not be too large and should not exceed the maximum number of available connections to the
     * database.
     */
    private const DEFAULT_MAX_CONCURRENT_TASK_COUNT = 60;

    /** Throttling value (in milliseconds) while achieving the maximum number of simultaneously executed tasks. */
    private const DEFAULT_AWAIT_DELAY = 20;

    /**
     * Current transport from which messages will be received.
     *
     * @var Transport
     */
    private $transport;

    /**
     * Handling incoming package processor.
     * Responsible for deserialization, routing and task execution.
     *
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
     * Collection of identifier of tasks that are being processed
     *
     * @psalm-var array<string, string>
     *
     * @var array
     */
    private $currentTasksInProgress = [];

    /**
     * Throttling value (in milliseconds) while achieving the maximum number of simultaneously executed tasks.
     *
     * @var int
     */
    private $awaitDelay;

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
     * Start queues listen.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail Connection refused
     */
    public function listen(Queue ...$queues): Promise
    {
        return $this->transport->consume(
            function (IncomingPackage $package): \Generator
            {
                /** Handle incoming package */
                $this->deferExecution($package);

                /** Limit the maximum number of concurrently running tasks */
                await:

                $inProgressCount = \count($this->currentTasksInProgress);

                if (($inProgressCount !== 0) && $inProgressCount >= $this->maxConcurrentTaskCount)
                {
                    $this->logger->debug(
                        'The maximum number of tasks has been reached',
                        [
                            'currentCount'      => $inProgressCount,
                            'currentCollection' => \array_values($this->currentTasksInProgress)
                        ]
                    );

                    yield delay($this->awaitDelay);

                    goto await;
                }
            },
            ...$queues
        );
    }

    /**
     * Unsubscribe all queues.
     * Terminates the subscription and stops the daemon.
     */
    public function stop(): void
    {
        Loop::defer(
            function (): \Generator
            {
                $this->logger->info('Subscriber stop command received');

                yield $this->transport->stop();

                await:

                $inProgressCount = \count($this->currentTasksInProgress);

                if ($inProgressCount !== 0)
                {
                    $this->logger->info(
                        'Waiting for the completion of all tasks taken',
                        [
                            'currentCount'      => $inProgressCount,
                            'currentCollection' => \array_keys($this->currentTasksInProgress)
                        ]
                    );

                    yield delay(1000);

                    goto await;
                }

                $this->logger->info('The event loop has been stopped');

                Loop::stop();
            }
        );
    }

    private function deferExecution(IncomingPackage $package): void
    {
        $this->currentTasksInProgress[$package->id()] = $package->id();

        Loop::defer(
            function () use ($package): void
            {
                $this->processor->handle($package)->onResolve(
                    function (?\Throwable $throwable) use ($package): \Generator
                    {
                        try
                        {
                            if ($throwable !== null)
                            {
                                throw $throwable;
                            }
                        }
                        catch (CancelledException | TimeoutException)
                        {
                            yield $package->reject(true);
                        }
                        catch (\Throwable $throwable)
                        {
                            $this->logThrowable($throwable, $package);
                        }
                        finally
                        {
                            unset($this->currentTasksInProgress[$package->id()]);
                        }
                    }
                );
            }
        );
    }

    private function logThrowable(\Throwable $throwable, IncomingPackage $package): void
    {
        $this->logger->critical(
            throwableMessage($throwable),
            \array_merge(
                throwableDetails($throwable),
                [
                    'packageId' => $package->id(),
                    'headers'   => $package->headers(),
                ]
            )
        );
    }
}
