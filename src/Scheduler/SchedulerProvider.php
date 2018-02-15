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

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Scheduler\Contract\Event\OperationScheduledEvent;
use Desperado\ServiceBus\Scheduler\Contract\Event\SchedulerOperationCanceledEvent;
use Desperado\ServiceBus\Scheduler\Contract\Event\SchedulerOperationEmittedEvent;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;
use Desperado\ServiceBus\Scheduler\Storage\SchedulerStorageInterface;

/**
 * Scheduler provider
 */
class SchedulerProvider
{
    /**
     * Storage
     *
     * @var SchedulerStorageInterface
     */
    private $storage;

    /**
     * Scheduler registry identifier
     *
     * @var string
     */
    private $registryIdentifier;

    /**
     * @param SchedulerStorageInterface $storage
     * @param string                    $registryNamespace
     */
    public function __construct(
        SchedulerStorageInterface $storage,
        string $registryNamespace = SchedulerRegistry::class
    )
    {
        $this->storage = $storage;
        $this->registryIdentifier = Uuid::v5($registryNamespace);
    }

    /**
     * @param ScheduledCommandIdentifier $id
     * @param AbstractCommand            $command
     * @param DateTime                   $executionDate
     * @param ExecutionContextInterface  $context
     *
     * @return void
     */
    public function scheduleCommand(
        ScheduledCommandIdentifier $id,
        AbstractCommand $command,
        DateTime $executionDate,
        ExecutionContextInterface $context
    ): void
    {
        $scheduledOperation = ScheduledOperation::new($id, $command, $executionDate);

        $registry = $this->addToRegistry($scheduledOperation);

        $context->delivery(
            OperationScheduledEvent::create([
                'id'               => $id->toString(),
                'commandNamespace' => \get_class($scheduledOperation->getCommand()),
                'executionDate'    => (string) $scheduledOperation->getDate(),
                'nextOperation'    => $registry->fetchNextOperation()
            ])
        );
    }

    /**
     * Emit command
     *
     * @param ScheduledCommandIdentifier $id
     * @param ExecutionContextInterface  $context
     *
     * @return void
     */
    public function emitCommand(ScheduledCommandIdentifier $id, ExecutionContextInterface $context): void
    {
        $registry = $this->obtainSchedulerRegistry();

        $operation = $registry->get($id);

        if(null !== $operation)
        {
            $registry = $this->removeFromRegistry($id);
            $command = $operation->getCommand();

            $context->delivery($command);

            $context->logContextMessage(
                \sprintf('The delayed "%s" command has been sent to the queue', \get_class($command))
            );
        }

        $context->delivery(
            SchedulerOperationEmittedEvent::create([
                'id'            => $id->toString(),
                'nextOperation' => $registry->fetchNextOperation()
            ])
        );
    }

    /**
     * Cancel scheduled command
     *
     * @param ScheduledCommandIdentifier $id
     * @param ExecutionContextInterface  $context
     * @param null|string                $reason
     *
     * @return void
     */
    public function cancelScheduledCommand(
        ScheduledCommandIdentifier $id,
        ExecutionContextInterface $context,
        ?string $reason = null

    ): void
    {
        $context->delivery(
            SchedulerOperationCanceledEvent::create([
                'id'            => $id,
                'reason'        => $reason,
                'nextOperation' => $this
                    ->removeFromRegistry($id)
                    ->fetchNextOperation()
            ])
        );
    }

    /**
     * Remove operation from registry
     *
     * @param ScheduledCommandIdentifier $id
     *
     * @return SchedulerRegistry
     */
    private function removeFromRegistry(ScheduledCommandIdentifier $id): SchedulerRegistry
    {
        $registry = $this->obtainSchedulerRegistry();
        $registry->remove($id);

        $this->storage->update($this->registryIdentifier, \serialize($registry));

        return $registry;
    }

    /**
     * Add operation to registry
     *
     * @param ScheduledOperation $operation
     *
     * @return SchedulerRegistry
     */
    private function addToRegistry(ScheduledOperation $operation): SchedulerRegistry
    {
        $registry = $this->obtainSchedulerRegistry();
        $registry->add($operation);

        $this->storage->update($this->registryIdentifier, \serialize($registry));

        return $registry;
    }

    /**
     * Load registry
     *
     * @return SchedulerRegistry
     */
    private function obtainSchedulerRegistry(): SchedulerRegistry
    {
        $registryData = $this->storage->load($this->registryIdentifier);
        $registry = '' !== (string) $registryData ? \unserialize($registryData) : null;

        if(null === $registry)
        {
            $registry = SchedulerRegistry::create();

            $this->storage->add($this->registryIdentifier, \serialize($registry));
        }

        return $registry;
    }
}
