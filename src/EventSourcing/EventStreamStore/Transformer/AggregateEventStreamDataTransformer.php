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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer;

use function Desperado\ServiceBus\Common\datetimeInstantiator;
use function Desperado\ServiceBus\Common\datetimeToString;
use Desperado\ServiceBus\EventSourcing\AggregateId;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEventStream;

/**
 * Converting aggregate events to a view for saving and back
 */
final class AggregateEventStreamDataTransformer
{
    /**
     * Events serializer
     *
     * @var AggregateEventSerializer
     */
    private $serializer;

    /**
     * @param AggregateEventSerializer $serializer
     */
    public function __construct(AggregateEventSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param AggregateEventStream $aggregateEvent
     *
     * @return StoredAggregateEventStream
     *
     * @throws \RuntimeException
     */
    public function streamToStoredRepresentation(AggregateEventStream $aggregateEvent): StoredAggregateEventStream
    {
        /** @var array<int, \Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent> $preparedEvents */
        $preparedEvents = \array_map(
            function(AggregateEvent $aggregateEvent): StoredAggregateEvent
            {
                return $this->eventToStoredRepresentation($aggregateEvent);
            },
            $aggregateEvent->events
        );

        return new StoredAggregateEventStream(
            (string) $aggregateEvent->id,
            \get_class($aggregateEvent->id),
            $aggregateEvent->aggregateClass,
            $preparedEvents,
            (string) datetimeToString($aggregateEvent->createdAt),
            datetimeToString($aggregateEvent->closedAt)
        );
    }

    /**
     * @param StoredAggregateEventStream $storedAggregateEventsStream
     *
     * @return AggregateEventStream
     *
     * @throws \RuntimeException
     */
    public function streamToDomainRepresentation(StoredAggregateEventStream $storedAggregateEventsStream): AggregateEventStream
    {
        $events = [];

        foreach($storedAggregateEventsStream->storedAggregateEvents as $storedAggregateEvent)
        {
            $events[] = $this->eventToDomainRepresentation($storedAggregateEvent);
        }

        /** @var \DateTimeImmutable $createdAt */
        $createdAt = datetimeInstantiator($storedAggregateEventsStream->createdAt);
        /** @var \DateTimeImmutable|null $closedAt */
        $closedAt = datetimeInstantiator($storedAggregateEventsStream->closedAt);

        /** @var AggregateId $id */
        $id = self::identifierInstantiator(
            $storedAggregateEventsStream->aggregateIdClass,
            $storedAggregateEventsStream->aggregateId
        );

        return new AggregateEventStream(
            $id, $storedAggregateEventsStream->aggregateClass, $events, $createdAt, $closedAt
        );
    }

    /**
     * @param AggregateEvent $aggregateEvent
     *
     * @return StoredAggregateEvent
     *
     * @throws \RuntimeException
     */
    public function eventToStoredRepresentation(AggregateEvent $aggregateEvent): StoredAggregateEvent
    {
        return new StoredAggregateEvent(
            $aggregateEvent->id,
            $aggregateEvent->playhead,
            $this->serializer->serialize($aggregateEvent->event),
            \get_class($aggregateEvent->event),
            (string) datetimeToString($aggregateEvent->occuredAt),
            datetimeToString($aggregateEvent->recordedAt)
        );
    }

    /**
     * @param StoredAggregateEvent $storedAggregateEvent
     *
     * @return AggregateEvent
     *
     * @throws \RuntimeException
     */
    public function eventToDomainRepresentation(StoredAggregateEvent $storedAggregateEvent): AggregateEvent
    {
        /** @var \DateTimeImmutable $occuredAt */
        $occuredAt = datetimeInstantiator($storedAggregateEvent->occuredAt);

        /** @var \DateTimeImmutable|null $recordedAt */
        $recordedAt = datetimeInstantiator($storedAggregateEvent->recordedAt);

        return new AggregateEvent(
            $storedAggregateEvent->eventId,
            $this->serializer->unserialize(
                $storedAggregateEvent->eventClass,
                $storedAggregateEvent->eventData
            ),
            $storedAggregateEvent->playheadPosition,
            $occuredAt,
            $recordedAt
        );
    }


    /**
     * Create identifier instance
     *
     * @template        AggregateId
     * @template-typeof AggregateId $idClass
     *
     * @param string $idClass
     * @param string $idValue
     *
     * @return AggregateId
     */
    private static function identifierInstantiator(string $idClass, string $idValue): AggregateId
    {
        return new $idClass($idValue);
    }
}
