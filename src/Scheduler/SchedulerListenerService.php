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
use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Scheduler\Commands\EmitSchedulerOperationCommand;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;
use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\ServiceInterface;
use Desperado\ServiceBus\Transport\RabbitMqTransport\RabbitMqConsumer;

/**
 * @Annotations\Services\Service(
 *     loggerChannel="scheduler"
 * )
 */
final class SchedulerListenerService implements ServiceInterface
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * @param string            $entryPointName
     * @param SchedulerProvider $schedulerProvider
     */
    public function __construct(string $entryPointName, SchedulerProvider $schedulerProvider)
    {
        $this->entryPointName = $entryPointName;
    }

    /**
     * Emit command
     *
     * @Annotations\Services\CommandHandler()
     *
     * @param EmitSchedulerOperationCommand $command
     * @param ExecutionContextInterface     $context
     * @param SchedulerProvider             $provider
     *
     * @return void
     *
     * @throws \Desperado\Domain\DateTimeException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\Domain\Message\Exceptions\OverwriteProtectedPropertyException
     * @throws \Desperado\Domain\Identity\Exceptions\EmptyIdentifierException
     */
    public function handleEmitSchedulerOperationCommand(
        EmitSchedulerOperationCommand $command,
        ExecutionContextInterface $context,
        SchedulerProvider $provider
    ): void
    {
        $provider->emitCommand(
            new ScheduledCommandIdentifier($command->getId()),
            $context
        );
    }

    /**
     * @Annotations\Services\EventHandler()
     *
     * @param Events\SchedulerOperationEmittedEvent $event
     * @param ExecutionContextInterface             $context
     *
     * @return void
     *
     * @throws \Desperado\Domain\Message\Exceptions\OverwriteProtectedPropertyException
     * @throws \Desperado\Domain\DateTimeException
     */
    public function whenSchedulerOperationEmittedEvent(
        Events\SchedulerOperationEmittedEvent $event,
        ExecutionContextInterface $context
    ): void
    {
        $this->processNextOperationEmit($event, $context);
    }

    /**
     * @Annotations\Services\EventHandler()
     *
     * @param Events\SchedulerOperationCanceledEvent $event
     * @param ExecutionContextInterface              $context
     *
     * @return void
     *
     * @throws \Desperado\Domain\Message\Exceptions\OverwriteProtectedPropertyException
     * @throws \Desperado\Domain\DateTimeException
     */
    public function whenSchedulerOperationCanceledEvent(
        Events\SchedulerOperationCanceledEvent $event,
        ExecutionContextInterface $context
    ): void
    {
        $this->processNextOperationEmit($event, $context);
    }

    /**
     * @Annotations\Services\EventHandler()
     *
     * @param Events\OperationScheduledEvent $event
     * @param ExecutionContextInterface      $context
     *
     * @return void
     *
     * @throws \Desperado\Domain\Message\Exceptions\OverwriteProtectedPropertyException
     * @throws \Desperado\Domain\DateTimeException
     */
    public function whenOperationScheduledEvent(
        Events\OperationScheduledEvent $event,
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
     *
     * @throws \Desperado\Domain\Message\Exceptions\OverwriteProtectedPropertyException
     * @throws \Desperado\Domain\DateTimeException
     */
    private function processNextOperationEmit(AbstractEvent $event, ExecutionContextInterface $context): void
    {
        /** @var Events\SchedulerOperationEmittedEvent|Events\SchedulerOperationCanceledEvent $event */

        $nextOperation = $event->getNextOperation();

        if(null !== $nextOperation)
        {
            $context->send(
                Commands\EmitSchedulerOperationCommand::create([
                    'id' => $nextOperation->getId()
                ]),
                MessageDeliveryOptions::create(
                    \sprintf('%s.timeout', $this->entryPointName),
                    null,
                    new ParameterBag([
                        'expiration'                               => 0,
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
