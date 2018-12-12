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
     * Stored events data
     *
     * @var array<int, \Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent>
     */
    public $storedAggregateEvents;

    /**
     * Stream created at datetime
     *
     * @var string
     */
    public $createdAt;

    /**
     * Stream closed at datetime
     *
     * @var string|null
     */
    public $closedAt;

    /**
     * @param string                                                                                $aggregateId
     * @param string                                                                                $aggregateIdClass
     * @param string                                                                                $aggregateClass
     * @param array<int, \Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent> $storedAggregateEvents
     * @param string                                                                                $createdAt
     * @param string|null                                                                           $closedAt
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
}
