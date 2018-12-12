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

namespace Desperado\ServiceBus\EventSourcing;

use Desperado\ServiceBus\EventSourcing\Contract\AggregateClosed;
use Desperado\ServiceBus\EventSourcing\Contract\AggregateCreated;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use function Desperado\ServiceBus\Common\datetimeInstantiator;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream;
use Desperado\ServiceBus\EventSourcing\Exceptions\AttemptToChangeClosedStream;

/**
 * Aggregate base class
 */
abstract class Aggregate
{
    public const   START_PLAYHEAD_INDEX = 0;
    private const  EVENT_APPLY_PREFIX   = 'on';

    private const INTERNAL_EVENTS = [
        AggregateCreated::class,
        AggregateClosed::class
    ];

    private const INCREASE_VERSION_STEP = 1;

    /**
     * Aggregate identifier
     *
     * @var AggregateId
     */
    private $id;

    /**
     * Current version
     *
     * @var int
     */
    private $version = self::START_PLAYHEAD_INDEX;

    /**
     * List of applied aggregate events
     *
     * @var array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent>
     */
    private $events;

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
     * @param AggregateId $id
     */
    final public function __construct(AggregateId $id)
    {
        $this->id = $id;

        $this->clearEvents();

        $this->raise(
            AggregateCreated::create((string) $id, \get_class($id), \get_class($this))
        );
    }

    /**
     * Receive id
     *
     * @return AggregateId
     */
    final public function id(): AggregateId
    {
        return $this->id;
    }

    /**
     * Raise (apply event)
     *
     * @param Event $event
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\EventSourcing\Exceptions\AttemptToChangeClosedStream
     */
    final protected function raise(Event $event): void
    {
        if(null !== $this->closedAt)
        {
            throw new AttemptToChangeClosedStream($this->id);
        }

        $specifiedEvent = $event;

        $this->attachEvent($specifiedEvent);
        $this->applyEvent($specifiedEvent);
    }

    /**
     * Receive aggregate version
     *
     * @return int
     */
    final public function version(): int
    {
        return $this->version;
    }

    /**
     * Close aggregate (make it read-only)
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\EventSourcing\Exceptions\AttemptToChangeClosedStream
     */
    final protected function close(): void
    {
        $this->raise(
            AggregateClosed::create((string) $this->id, \get_class($this->id), \get_class($this))
        );
    }

    /**
     * On aggregate closed
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param AggregateClosed $event
     *
     * @return void
     */
    private function onAggregateClosed(AggregateClosed $event): void
    {
        $this->closedAt = $event->datetime;
    }

    /**
     * On aggregate created
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param AggregateCreated $event
     *
     * @return void
     */
    private function onAggregateCreated(AggregateCreated $event): void
    {
        $this->createdAt = $event->datetime;
    }

    /**
     * Receive uncommitted events as stream
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          EventSourcingProvider::save()
     *
     * @return AggregateEventStream
     */
    private function makeStream(): AggregateEventStream
    {
        /** @var array<int, \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent> $events */
        $events = $this->events;

        $this->clearEvents();

        return new AggregateEventStream(
            $this->id,
            \get_class($this),
            $events,
            $this->createdAt,
            $this->closedAt
        );
    }

    /**
     * Restore from event stream
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          EventSourcingProvider::load()
     *
     * @param AggregateEventStream $aggregateEventsStream
     *
     * @return void
     */
    private function appendStream(AggregateEventStream $aggregateEventsStream): void
    {
        $this->clearEvents();

        $this->id = $aggregateEventsStream->id;

        /** @var AggregateEvent $aggregateEvent */
        foreach($aggregateEventsStream->events as $aggregateEvent)
        {
            $this->applyEvent($aggregateEvent->event);

            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->increaseVersion(self::INCREASE_VERSION_STEP);
        }
    }

    /**
     * Attach event to stream
     *
     * @param Event $event
     *
     * @return void
     */
    private function attachEvent(Event $event): void
    {
        $this->increaseVersion(self::INCREASE_VERSION_STEP);

        /** @var \DateTimeImmutable $currentDate */
        $currentDate = datetimeInstantiator('NOW');

        $this->events[] = new AggregateEvent(uuid(), $event, $this->version, $currentDate);
    }

    /**
     * Apply event
     *
     * @param Event $event
     *
     * @return void
     */
    private function applyEvent(Event $event): void
    {
        $eventListenerMethodName = self::createListenerName($event);

        true === self::isInternalEvent($event)
            ? $this->processInternalEvent($eventListenerMethodName, $event)
            : $this->processChildEvent($eventListenerMethodName, $event);
    }

    /**
     * Is internal event (for current class)
     *
     * @param Event $event
     *
     * @return bool
     */
    private static function isInternalEvent(Event $event): bool
    {
        return true === \in_array(\get_class($event), self::INTERNAL_EVENTS, true);
    }

    /**
     * @param string $listenerName
     * @param Event  $event
     *
     * @return void
     */
    private function processInternalEvent(string $listenerName, Event $event): void
    {
        $this->{$listenerName}($event);
    }

    /**
     * @param string $listenerName
     * @param Event  $event
     *
     * @return void
     */
    private function processChildEvent(string $listenerName, Event $event): void
    {
        /**
         * Call child class method
         *
         * @param Event $event
         *
         * @return void
         */
        $closure = function(Event $event) use ($listenerName): void
        {
            if(true === \method_exists($this, $listenerName))
            {
                $this->{$listenerName}($event);
            }
        };

        $closure->call($this, $event);
    }

    /**
     * Create event listener name
     *
     * @param Event $event
     *
     * @return string
     */
    private static function createListenerName(Event $event): string
    {
        $eventListenerMethodNameParts = \explode('\\', \get_class($event));

        return \sprintf(
            '%s%s',
            self::EVENT_APPLY_PREFIX,
            \end($eventListenerMethodNameParts)
        );
    }

    /**
     * Increase aggregate version
     *
     * @param int $step
     *
     * @return void
     */
    private function increaseVersion(int $step): void
    {
        $this->version += $step;
    }

    /**
     * Clear all aggregate events
     *
     * @return void
     */
    private function clearEvents(): void
    {
        $this->events = [];
    }
}
