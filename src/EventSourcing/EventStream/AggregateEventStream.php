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

namespace Desperado\ServiceBus\EventSourcing\EventStream;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\EventSourcing\AggregateId;

/**
 *
 */
final class AggregateEventStream
{
    /**
     * Stream (aggregate) identifier
     *
     * @var AggregateId
     */
    public $id;

    /**
     * Aggregate class
     *
     * @var string
     */
    public $aggregateClass;

    /**
     * Event collection
     *
     * @var array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>
     */
    public $events;

    /**
     * Origin event collection
     *
     * @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event>
     */
    public $originEvents;

    /**
     * Created at datetime
     *
     * @var \DateTimeImmutable
     */
    public $createdAt;

    /**
     * Closed at datetime
     *
     * @var \DateTimeImmutable|null
     */
    public $closedAt;

    /**
     * @param AggregateId             $id
     * @param string                  $aggregateClass
     * @param array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent> $events
     * @param \DateTimeImmutable      $createdAt
     * @param \DateTimeImmutable|null $closedAt
     */
    public function __construct(
        AggregateId $id,
        string $aggregateClass,
        array $events,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $closedAt
    )
    {
        $this->id             = $id;
        $this->aggregateClass = $aggregateClass;
        $this->events         = self::sortEvents($events);
        $this->originEvents   = self::extractOriginEvents($this->events);
        $this->createdAt      = $createdAt;
        $this->closedAt       = $closedAt;
    }

    /**
     * @param array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent> $events
     *
     * @return array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>
     */
    private static function sortEvents(array $events): array
    {
        $result = [];

        foreach($events as $aggregateEvent)
        {
            /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent $aggregateEvent */

            $result[$aggregateEvent->playhead] = $aggregateEvent;
        }

        \ksort($result);

        return $result;
    }

    /**
     * @param array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent> $events
     *
     * @return array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event>
     */
    private static function extractOriginEvents(array $events): array
    {
        return \array_map(
            static function(AggregateEvent $event): Event
            {
                return $event->event;
            },
            $events
        );
    }
}
