<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler;

use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\Domain\ParameterBag;
use Desperado\Domain\Transport\Message\MessageDeliveryOptions;
use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;
use Desperado\ServiceBus\ServiceInterface;
use Desperado\ServiceBus\Scheduler\Contract;
use Desperado\ServiceBus\Transport\RabbitMqTransport\RabbitMqConsumer;

/**
 * @Annotations\Services\Service(
 *     loggerChannel="scheduler"
 * )
 */
class SchedulerListenerService implements ServiceInterface
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Scheduler provider
     *
     * @var SchedulerProvider
     */
    private $schedulerProvider;

    /**
     * @param string            $entryPointName
     * @param SchedulerProvider $schedulerProvider
     */
    public function __construct(string $entryPointName, SchedulerProvider $schedulerProvider)
    {
        $this->entryPointName = $entryPointName;
        $this->schedulerProvider = $schedulerProvider;
    }

    /**
     * @Annotations\Services\CommandHandler()
     *
     * @param Contract\Command\EmitSchedulerOperationCommand $command
     * @param ExecutionContextInterface                      $context
     *
     * @return void
     */
    public function executeEmitOperation(
        Contract\Command\EmitSchedulerOperationCommand $command,
        ExecutionContextInterface $context
    ): void
    {
        $this->schedulerProvider->emitCommand(
            new ScheduledCommandIdentifier($command->getId()),
            $context
        );
    }

    /**
     * @Annotations\Services\CommandHandler()
     *
     * @param Contract\Command\CancelSchedulerOperationCommand $command
     * @param ExecutionContextInterface                        $context
     *
     * @return void
     */
    public function executeCancelSchedulerOperationCommand(
        Contract\Command\CancelSchedulerOperationCommand $command,
        ExecutionContextInterface $context
    ): void
    {
        $this->schedulerProvider->cancelScheduledCommand(
            new ScheduledCommandIdentifier($command->getId()),
            $context,
            $command->getReason()
        );
    }

    /**
     * @Annotations\Services\EventHandler()
     *
     * @param Contract\Event\SchedulerOperationEmittedEvent $event
     * @param ExecutionContextInterface                     $context
     *
     * @return void
     */
    public function whenSchedulerOperationEmittedEvent(
        Contract\Event\SchedulerOperationEmittedEvent $event,
        ExecutionContextInterface $context
    ): void
    {
        $this->processNextOperationEmit($event, $context);
    }

    /**
     * @Annotations\Services\EventHandler()
     *
     * @param Contract\Event\SchedulerOperationCanceledEvent $event
     * @param ExecutionContextInterface                      $context
     *
     * @return void
     */
    public function whenSchedulerOperationCanceledEvent(
        Contract\Event\SchedulerOperationCanceledEvent $event,
        ExecutionContextInterface $context
    ): void
    {
        $this->processNextOperationEmit($event, $context);
    }

    /**
     * @Annotations\Services\EventHandler()
     *
     * @param Contract\Event\OperationScheduledEvent $event
     * @param ExecutionContextInterface              $context
     *
     * @return void
     */
    public function whenOperationScheduledEvent(
        Contract\Event\OperationScheduledEvent $event,
        ExecutionContextInterface $context
    ): void
    {
        $this->processNextOperationEmit($event, $context);
    }

    /**
     * Emit next operation
     *
     * @param AbstractEvent             $event
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    private function processNextOperationEmit(AbstractEvent $event, ExecutionContextInterface $context): void
    {
        /** @var Contract\Event\SchedulerOperationEmittedEvent|Contract\Event\SchedulerOperationCanceledEvent $event */

        $nextOperation = $event->getNextOperation();

        if(null !== $nextOperation)
        {
            $context->send(
                Contract\Command\EmitSchedulerOperationCommand::create([
                    'id' => $event->getId()
                ]),
                MessageDeliveryOptions::create(
                    \sprintf('%s.timeout', $this->entryPointName),
                    null,
                    new ParameterBag([
                        'expiration' => 0,
                        RabbitMqConsumer::HEADER_DELIVERY_MODE_KEY => RabbitMqConsumer::PERSISTED_DELIVERY_MODE,
                        RabbitMqConsumer::HEADER_DELAY_KEY         => $this->calculateExecutionDelay($nextOperation)
                    ])
                )
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
    private function calculateExecutionDelay(NextScheduledOperation $nextScheduledOperation): int
    {
        $executionDelay = $nextScheduledOperation->getTime() - (int) (\microtime(true) * 1000);

        if(0 > $executionDelay)
        {
            $executionDelay *= -1;
        }

        return $executionDelay;
    }
}
