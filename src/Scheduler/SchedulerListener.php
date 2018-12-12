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

use Amp\Coroutine;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
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
     * @return Promise It does not return any result
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
             * @var \Generator $generator
             */
            $generator = invokeReflectionMethod($schedulerProvider, 'emit', $command->id, $context);

            return new Coroutine($generator);
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            $context->logContextMessage(
                'Emit scheduled operation "{scheduledOperationId}" failed with message "{throwableMessage}"', [
                    'scheduledOperationId' => (string) $command->id,
                    'throwableMessage'     => $throwable->getMessage(),
                    'throwablePoint'       => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                ]
            );
        }

        return new Success();
        // @codeCoverageIgnoreEnd
    }

    /**
     * Scheduler operation emitted
     *
     * @EventListener()
     *
     * @param SchedulerOperationEmitted $event
     * @param KernelContext             $context
     * @param SchedulerProvider         $schedulerProvider
     *
     * @return \Generator
     */
    public function whenSchedulerOperationEmitted(
        SchedulerOperationEmitted $event,
        KernelContext $context,
        SchedulerProvider $schedulerProvider
    ): \Generator
    {
        yield self::processNextOperationEmit($event, $schedulerProvider, $context);
    }

    /**
     * Scheduler operation canceled
     *
     * @EventListener()
     *
     * @param SchedulerOperationCanceled $event
     * @param KernelContext              $context
     * @param SchedulerProvider          $schedulerProvider
     *
     *
     * @return \Generator
     */
    public function whenSchedulerOperationCanceled(
        SchedulerOperationCanceled $event,
        KernelContext $context,
        SchedulerProvider $schedulerProvider
    ): \Generator
    {
        yield self::processNextOperationEmit($event, $schedulerProvider, $context);
    }

    /**
     * Operation scheduled
     *
     * @EventListener()
     *
     * @param OperationScheduled $event
     * @param KernelContext      $context
     * @param SchedulerProvider  $schedulerProvider
     *
     * @return \Generator
     */
    public function whenOperationScheduled(
        OperationScheduled $event,
        KernelContext $context,
        SchedulerProvider $schedulerProvider
    ): \Generator
    {
        yield self::processNextOperationEmit($event, $schedulerProvider, $context);
    }

    /**
     * Emit next operation
     *
     * @param Event             $event
     * @param SchedulerProvider $schedulerProvider
     * @param KernelContext     $context
     *
     * @return Promise It does not return any result
     */
    private static function processNextOperationEmit(
        Event $event,
        SchedulerProvider $schedulerProvider,
        KernelContext $context
    ): Promise
    {
        if(
            true === ($event instanceof SchedulerOperationEmitted) ||
            true === ($event instanceof SchedulerOperationCanceled) ||
            true === ($event instanceof OperationScheduled)
        )
        {
            /** @var SchedulerOperationEmitted|SchedulerOperationCanceled|OperationScheduled $event */

            try
            {

                /**
                 * @see SchedulerProvider::emitNextOperation()
                 *
                 * @var \Generator $generator
                 */
                $generator = invokeReflectionMethod(
                    $schedulerProvider,
                    'emitNextOperation',
                    $event->nextOperation,
                    $context
                );

                return new Coroutine($generator);
            }
                // @codeCoverageIgnoreStart
            catch(\Throwable $throwable)
            {
                return new Failure($throwable);
            }
        }

        return new Failure(
            new \LogicException('Invalid event type specified')
        );
        // @codeCoverageIgnoreEnd
    }
}
