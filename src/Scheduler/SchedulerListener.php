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

namespace Desperado\ServiceBus\Scheduler;

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\Messages\Command\EmitSchedulerOperation;
use Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationCanceled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationEmitted;
use Desperado\ServiceBus\SchedulerProvider;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class SchedulerListener
{
    /**
     * Emit command
     *
     * @CommandHandler()
     *
     * @param EmitSchedulerOperation $command
     * @param KernelContext          $context
     * @param SchedulerProvider      $schedulerProvider
     *
     * @return Promise<null>
     */
    public function handleEmit(
        EmitSchedulerOperation $command,
        KernelContext $context,
        SchedulerProvider $schedulerProvider
    ): Promise
    {
        try
        {
            /**
             * @see SchedulerProvider::emit()
             *
             * @var Promise $promise
             */
            $promise = invokeReflectionMethod($schedulerProvider, 'emit', $command->id(), $context);

            return $promise;
        }
        catch(\Throwable $throwable)
        {
            $context->logContextMessage(
                'Emit scheduled operation "{scheduledOperationId}" failed with message "{throwableMessage}"', [
                    'scheduledOperationId' => (string) $command->id(),
                    'throwableMessage'     => $throwable->getMessage(),
                    'throwablePoint'       => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                ]
            );
        }

        return new Success();
    }

    /**
     * Scheduler operation emitted
     *
     * @EventListener()
     *
     * @param SchedulerOperationEmitted $event
     * @param KernelContext             $context
     *
     * @return Promise<null>
     */
    public function whenSchedulerOperationEmitted(SchedulerOperationEmitted $event, KernelContext $context): Promise
    {
        return self::processNextOperationEmit($event, $context);
    }

    /**
     * Scheduler operation canceled
     *
     * @EventListener()
     *
     * @param SchedulerOperationCanceled $event
     * @param KernelContext              $context
     *
     * @return Promise<null>
     */
    public function whenSchedulerOperationCanceled(SchedulerOperationCanceled $event, KernelContext $context): Promise
    {
        return self::processNextOperationEmit($event, $context);
    }

    /**
     * Operation scheduled
     *
     * @EventListener()
     *
     * @param OperationScheduled $event
     * @param KernelContext      $context
     *
     * @return Promise<null>
     */
    public function whenOperationScheduled(OperationScheduled $event, KernelContext $context): Promise
    {
        return self::processNextOperationEmit($event, $context);
    }

    /**
     * Emit next operation
     *
     * @param Event         $event
     * @param KernelContext $context
     *
     * @return Promise<null>
     */
    private static function processNextOperationEmit(Event $event, KernelContext $context): Promise
    {
        /** @var SchedulerOperationEmitted|SchedulerOperationCanceled|OperationScheduled $event */

        $nextOperation = $event->nextOperation();

        if(null !== $nextOperation)
        {
            /** Send a message that will return after a specified time interval */
            return $context->send(
                EmitSchedulerOperation::create(new ScheduledOperationId($nextOperation->id())), [
                    'expiration' => 0,
                    'x-delay'    => self::calculateExecutionDelay($nextOperation)
                ]
            );
        }

        return new Success();
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
        /** @noinspection UnnecessaryCastingInspection */
        $executionDelay = $nextScheduledOperation->time() - (int) (\microtime(true) * 1000);

        if(0 > $executionDelay)
        {
            $executionDelay *= -1;
        }

        return $executionDelay;
    }
}
