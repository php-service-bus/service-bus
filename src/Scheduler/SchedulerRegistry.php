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

use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;

/**
 * Scheduled tasks list
 */
final class SchedulerRegistry implements \Serializable
{
    public const GZIP_LEVEL = 6;

    /**
     * Operations collection
     *
     * @var ScheduledOperation
     */
    private $operations = [];

    /**
     * Time table of operations in milliseconds
     *
     * @var int[]
     */
    private $timetable;

    /**
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return \base64_encode(
            \gzencode(
                \serialize([
                        $this->operations,
                        $this->timetable
                    ]
                ),
                self::GZIP_LEVEL
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $serialized = \gzdecode(\base64_decode($serialized));

        [$this->operations, $this->timetable] = \unserialize($serialized);
    }

    /**
     * Add operation to registry
     *
     * @param ScheduledOperation $scheduledOperation
     *
     * @return void
     */
    public function add(ScheduledOperation $scheduledOperation): void
    {
        $id = $scheduledOperation->getId()->toString();

        $this->operations[$id] = $scheduledOperation;
        $this->timetable[$id] = (int) $scheduledOperation->getDate()->toString('U.u') * 1000;
    }

    /**
     * Get operation
     *
     * @param ScheduledCommandIdentifier $id
     *
     * @return ScheduledOperation|null
     */
    public function get(ScheduledCommandIdentifier $id): ?ScheduledOperation
    {
        return true === $this->has($id)
            ? $this->operations[$id->toString()]
            : null;
    }

    /**
     * Remove from registry
     *
     * @param ScheduledCommandIdentifier $id
     *
     * @return void
     */
    public function remove(ScheduledCommandIdentifier $id): void
    {
        $identifier = $id->toString();

        unset($this->timetable[$identifier], $this->operations[$identifier]);
    }

    /**
     * Has command in registry
     *
     * @param ScheduledCommandIdentifier $id
     *
     * @return bool
     */
    public function has(ScheduledCommandIdentifier $id): bool
    {
        $identifier = $id->toString();

        return true === isset($this->timetable[$identifier]) && true === isset($this->operations[$identifier]);
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
            $id = \array_search($minTime, $this->timetable, true);

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
        $this->timetable = [];
    }
}
