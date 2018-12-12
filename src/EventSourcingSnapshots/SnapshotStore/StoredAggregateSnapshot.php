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

namespace Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore;

/**
 * Aggregate snapshot stored representation
 */
final class StoredAggregateSnapshot
{
    /**
     * Aggregate id
     *
     * @var string
     */
    public $aggregateId;

    /**
     * Aggregate id class
     *
     * @var string
     */
    public $aggregateIdClass;

    /**
     * Aggregate class
     *
     * @var string
     */
    public $aggregateClass;

    /**
     * Aggregate version
     *
     * @var int
     */
    public $version;

    /**
     * Serialized aggregate data
     *
     * @var string
     */
    public $payload;

    /**
     * Snapshot creation date
     *
     * @var string
     */
    public $createdAt;

    /**
     * @param string $aggregateId
     * @param string $aggregateIdClass
     * @param string $aggregateClass
     * @param int    $version
     * @param string $payload
     * @param string $createdAt
     */
    public function __construct(
        string $aggregateId,
        string $aggregateIdClass,
        string $aggregateClass,
        int $version,
        string $payload,
        string $createdAt
    )
    {
        $this->aggregateId      = $aggregateId;
        $this->aggregateIdClass = $aggregateIdClass;
        $this->aggregateClass   = $aggregateClass;
        $this->version          = $version;
        $this->payload          = $payload;
        $this->createdAt        = $createdAt;
    }
}
