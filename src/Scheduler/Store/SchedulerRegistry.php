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

namespace Desperado\ServiceBus\Scheduler\Store;

use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Registry representation
 */
final class SchedulerRegistry implements \Serializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * Operations collection
     *
     * @var array<string, \Desperado\ServiceBus\Scheduler\Data\ScheduledOperation>
     */
    private $operations;

    /**
     * Time table of operations in milliseconds
     *
     * @var array<string, int>
     */
    private $timetable;

    /**
     * Create registry
     *
     * @return self
     */
    public static function create(string $registryId): self
    {
        $self = new self();

        $self->id = $registryId;

        return $self;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return \serialize([$this->id, $this->operations, $this->timetable]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized): void
    {
        [$this->id, $this->operations, $this->timetable] = \unserialize($serialized, ['allowed_classes' => true]);
    }

    /**
     * Add operation to registry
     *
     * @param ScheduledOperation $operation
     *
     * @return void
     */
    public function add(ScheduledOperation $operation): void
    {
        $operationId = (string) $operation->id();

        $this->operations[$operationId] = $operation;
        $this->timetable[$operationId]  = (int) $operation->date()->format('U.u') * 1000;
    }

    /**
     * Extract operation from registry
     *
     * @param ScheduledOperationId $id
     *
     * @return ScheduledOperation|null
     */
    public function extract(ScheduledOperationId $id): ?ScheduledOperation
    {
        $identifier = (string) $id;

        if(true === isset($this->timetable[$identifier]) && true === isset($this->operations[$identifier]))
        {
            $operation = $this->operations[$identifier];

            $this->remove($id);

            return $operation;
        }

        return null;
    }

    /**
     * Remove from registry
     *
     * @param ScheduledOperationId $id
     *
     * @return void
     */
    public function remove(ScheduledOperationId $id): void
    {
        $identifier = (string) $id;

        unset($this->timetable[$identifier], $this->operations[$identifier]);
    }

    /**
     * Need to call after remove or add of new operation
     *
     * @return NextScheduledOperation|null
     */
    public function fetchNextOperation(): ?NextScheduledOperation
    {
        if(0 !== \count($this->timetable))
        {
            $minTime = \min($this->timetable);
            $id      = (string) \array_search($minTime, $this->timetable, true);

            // @codeCoverageIgnoreStart
            if('' === $id)
            {
                return null;
            }
            // @codeCoverageIgnoreEnd

            /** @var ScheduledOperation $operation */
            $operation = $this->operations[$id];

            if(false === $operation->isSent())
            {
                $this->operations[$id] = $operation->sent();

                return new NextScheduledOperation($id, $minTime);
            }
        }

        return null;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->operations = [];
        $this->timetable  = [];
    }
}
