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

namespace Desperado\ServiceBus;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use function Desperado\ServiceBus\Common\datetimeInstantiator;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\Endpoint\DeliveryOptions;
use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\Exceptions\DuplicateScheduledJob;
use Desperado\ServiceBus\Scheduler\Exceptions\ScheduledOperationNotFound;
use Desperado\ServiceBus\Scheduler\Exceptions\SchedulerFailure;
use Desperado\ServiceBus\Scheduler\Messages\Command\EmitSchedulerOperation;
use Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationCanceled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationEmitted;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use Desperado\ServiceBus\Scheduler\Store\SchedulerStore;
use Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed;
use Psr\Log\LogLevel;

/**
 *
 */
final class SchedulerProvider
{
    /**
     * @var SchedulerStore
     */
    private $store;

    /**
     * @param SchedulerStore $store
     */
    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    /**
     * Schedule operation
     *
     * @param ScheduledOperationId   $id
     * @param Command                $command
     * @param \DateTimeImmutable     $executionDate
     * @param MessageDeliveryContext $context
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\DuplicateScheduledJob
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\SchedulerFailure
     */
    public function schedule(
        ScheduledOperationId $id,
        Command $command,
        \DateTimeImmutable $executionDate,
        MessageDeliveryContext $context
    ): Promise
    {
        $store = $this->store;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperation $operation) use ($store, $context): \Generator
            {
                try
                {
                    $generator = $store->add(
                        $operation,
                        static function(ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): \Generator
                        {
                            yield $context->delivery(
                                OperationScheduled::create(
                                    $operation->id,
                                    $operation->command,
                                    $operation->date,
                                    $nextOperation
                                )
                            );
                        }
                    );

                    yield $generator;

                    if($context instanceof LoggingInContext)
                    {
                        $context->logContextMessage('Operation "{messageClass}" scheduled', [
                                'messageClass' => \get_class($operation->command)
                            ]
                        );
                    }
                }
                catch(UniqueConstraintViolationCheckFailed $exception)
                {
                    throw new DuplicateScheduledJob(
                        \sprintf('Job with ID "%s" already exists', $operation->id),
                        $exception->getCode(),
                        $exception
                    );
                }
                catch(\Throwable $throwable)
                {
                    throw new SchedulerFailure(
                        $throwable->getMessage(),
                        $throwable->getCode(),
                        $throwable
                    );
                }
            },
            ScheduledOperation::new($id, $command, $executionDate)
        );
    }

    /**
     * Cancel scheduled operation
     *
     * @param ScheduledOperationId   $id
     * @param MessageDeliveryContext $context
     * @param string|null            $reason
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\SchedulerFailure
     */
    public function cancel(ScheduledOperationId $id, MessageDeliveryContext $context, ?string $reason = null): Promise
    {
        $store = $this->store;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id, ?string $reason = null) use ($store, $context): \Generator
            {
                try
                {
                    $generator = $store->remove(
                        $id,
                        static function(?NextScheduledOperation $nextOperation) use ($id, $reason, $context): \Generator
                        {
                            yield $context->delivery(
                                SchedulerOperationCanceled::create($id, $reason, $nextOperation)
                            );
                        }
                    );

                    yield $generator;
                }
                catch(\Throwable $throwable)
                {
                    throw new SchedulerFailure(
                        $throwable->getMessage(),
                        $throwable->getCode(),
                        $throwable
                    );
                }
            },
            $id, $reason
        );
    }

    /**
     * Emit operation
     * Called by infrastructure components
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param ScheduledOperationId   $id
     * @param MessageDeliveryContext $context
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\SchedulerFailure
     */
    private function emit(ScheduledOperationId $id, MessageDeliveryContext $context): \Generator
    {
        try
        {
            /** @var callable(ScheduledOperation|null, ?NextScheduledOperation|null):\Generator $closure */
            $closure = static function(?ScheduledOperation $operation, ?NextScheduledOperation $nextOperation) use ($context): \ Generator
            {
                if(null !== $operation)
                {
                    yield self::processSendCommand($operation, $context);

                    yield $context->delivery(
                        SchedulerOperationEmitted::create($operation->id, $nextOperation)
                    );
                }
            };

            $generator = $this->store->extract($id, $closure);

            yield $generator;
        }
        catch(ScheduledOperationNotFound $exception)
        {
            if($context instanceof LoggingInContext)
            {
                $context->logContextThrowable($exception);
            }

            yield $context->delivery(
                SchedulerOperationEmitted::create($id, null)
            );
        }
        catch(\Throwable $throwable)
        {
            throw new SchedulerFailure(
                $throwable->getMessage(),
                $throwable->getCode(),
                $throwable
            );
        }
    }

    /**
     * Emit next operation
     * Called by infrastructure components
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param NextScheduledOperation|null $nextOperation
     * @param MessageDeliveryContext      $context
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\SchedulerFailure
     */
    private function emitNextOperation(?NextScheduledOperation $nextOperation, MessageDeliveryContext $context): \Generator
    {
        try
        {
            if(null !== $nextOperation)
            {
                $id    = $nextOperation->id;
                $delay = self::calculateExecutionDelay($nextOperation);

                /** Message will return after a specified time interval */
                yield $context->delivery(
                    EmitSchedulerOperation::create($id),
                    new DeliveryOptions(['x-delay' => $delay])
                );

                if($context instanceof LoggingInContext)
                {
                    $context->logContextMessage(
                        'Scheduled operation with identifier "{scheduledOperationId}" will be executed in "{scheduledOperationDelay}" seconds', [
                            'scheduledOperationId'    => $id,
                            'scheduledOperationDelay' => $delay / 1000
                        ]
                    );
                }

                return null;
            }

            if($context instanceof LoggingInContext)
            {
                $context->logContextMessage('Next operation not specified', [], LogLevel::DEBUG);
            }
        }
        catch(\Throwable $throwable)
        {
            throw new SchedulerFailure(
                $throwable->getMessage(),
                $throwable->getCode(),
                $throwable
            );
        }
    }

    /**
     * Calculate next execution delay
     *
     * @param NextScheduledOperation $nextScheduledOperation
     *
     * @return int
     */
    private static function calculateExecutionDelay(NextScheduledOperation $nextScheduledOperation): int
    {
        /** @var \DateTimeImmutable $currentDate */
        $currentDate = datetimeInstantiator('NOW');

        /** @noinspection UnnecessaryCastingInspection */
        $executionDelay = $nextScheduledOperation->time->getTimestamp() - $currentDate->getTimestamp();

        return $executionDelay * 1000;
    }

    /**
     * @param ScheduledOperation     $operation
     * @param MessageDeliveryContext $context
     *
     * @return Promise It does not return any result
     */
    private static function processSendCommand(ScheduledOperation $operation, MessageDeliveryContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperation $operation, MessageDeliveryContext $context): \Generator
            {
                yield $context->delivery($operation->command);

                if($context instanceof LoggingInContext)
                {
                    $context->logContextMessage(
                        'The delayed "{messageClass}" command has been sent to the transport', [
                            'messageClass'         => \get_class($operation->command),
                            'scheduledOperationId' => (string) $operation->id
                        ]
                    );
                }
            },
            $operation, $context
        );
    }
}
