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
    private $aggregateId;

    /**
     * Aggregate id class
     *
     * @var string
     */
    private $aggregateIdClass;

    /**
     * Aggregate class
     *
     * @var string
     */
    private $aggregateClass;

    /**
     * Aggregate version
     *
     * @var int
     */
    private $version;

    /**
     * Serialized aggregate data
     *
     * @var string
     */
    private $payload;

    /**
     * Snapshot creation date
     *
     * @var string
     */
    private $createdAt;

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

    /**
     * Receive aggregate id
     *
     * @return string
     */
    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    /**
     * Receive aggregate id class
     *
     * @return string
     */
    public function aggregateIdClass(): string
    {
        return $this->aggregateIdClass;
    }

    /**
     * Receive aggregate class
     *
     * @return string
     */
    public function aggregateClass(): string
    {
        return $this->aggregateClass;
    }

    /**
     * Receive aggregate version
     *
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }

    /**
     * Receive serialized aggregate data
     *
     * @return string
     */
    public function payload(): string
    {
        return $this->payload;
    }

    /**
     * Receive creation date
     *
     * @return string
     */
    public function createdAt(): string
    {
        return $this->createdAt;
    }
}
