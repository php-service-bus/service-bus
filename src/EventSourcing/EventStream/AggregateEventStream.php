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
    private $id;

    /**
     * Aggregate class
     *
     * @var string
     */
    private $aggregateClass;

    /**
     * Event collection
     *
     * @var array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>
     */
    private $events;

    /**
     * Origin event collection
     *
     * @var array<mixed, \Desperado\ServiceBus\Common\Contract\Messages\Event>
     */
    private $originEvents;

    /**
     * Created at datetime
     *
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * Closed at datetime
     *
     * @var \DateTimeImmutable|null
     */
    private $closedAt;

    /**
     * @param AggregateId             $id
     * @param string                  $aggregateClass
     * @param \SplObjectStorage<\Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>  $events
     * @param \DateTimeImmutable      $createdAt
     * @param \DateTimeImmutable|null $closedAt
     */
    public function __construct(
        AggregateId $id,
        string $aggregateClass,
        \SplObjectStorage $events,
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
     * Receive aggregate (stream) identifier
     *
     * @return AggregateId
     */
    public function id(): AggregateId
    {
        return $this->id;
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
     * Receive aggregate events collection
     *
     * @return array
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Receive original events collection
     *
     * @return array
     */
    public function originEvents(): array
    {
        return $this->originEvents;
    }

    /**
     * Receive created at datetime
     *
     * @return \DateTimeImmutable
     */
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Receive closed at datetime
     *
     * @return \DateTimeImmutable|null
     */
    public function closedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    /**
     * @param \SplObjectStorage<\Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>  $events
     *
     * @return array
     */
    private static function sortEvents(\SplObjectStorage $storage): array
    {
        $result = [];

        foreach($storage as $aggregateEvent)
        {
            /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent $aggregateEvent */

            $result[$aggregateEvent->playhead()] = $aggregateEvent;
        }

        \ksort($result);

        return $result;
    }

    /**
     * @param array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>
     *
     * @return array<mixed, \Desperado\ServiceBus\Common\Contract\Messages\Event>
     */
    private static function extractOriginEvents(array $events): array
    {
        return \array_map(
            static function(AggregateEvent $event): Event
            {
                return $event->event();
            },
            $events
        );
    }
}
