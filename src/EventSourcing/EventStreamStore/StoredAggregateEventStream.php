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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore;

/**
 *
 */
final class StoredAggregateEventStream
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
     * Stored events data
     *
     * @var array<mixed, \Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent>
     */
    private $storedAggregateEvents;

    /**
     * Stream created at datetime
     *
     * @var string
     */
    private $createdAt;

    /**
     * Stream closed at datetime
     *
     * @var string|null
     */
    private $closedAt;

    /**
     * @param string      $aggregateId
     * @param string      $aggregateIdClass
     * @param string      $aggregateClass
     * @param array       $storedAggregateEvents
     * @param string      $createdAt
     * @param string|null $closedAt
     */
    public function __construct(
        string $aggregateId,
        string $aggregateIdClass,
        string $aggregateClass,
        array $storedAggregateEvents,
        string $createdAt,
        ?string $closedAt = null
    )
    {
        $this->aggregateId           = $aggregateId;
        $this->aggregateIdClass      = $aggregateIdClass;
        $this->aggregateClass        = $aggregateClass;
        $this->storedAggregateEvents = $storedAggregateEvents;
        $this->createdAt             = $createdAt;
        $this->closedAt              = $closedAt;
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
    public function getAggregateIdClass(): string
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
     * Receive stored events
     *
     * @return array<mixed, \Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent>
     */
    public function aggregateEvents(): array
    {
        return $this->storedAggregateEvents;
    }

    /**
     * Receive created at datetime
     *
     * @return string
     */
    public function createdAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Receive closed at datetime
     *
     * @return string|null
     */
    public function closedAt(): ?string
    {
        return $this->closedAt;
    }
}
