<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\EventSourcing;

use Desperado\Framework\Common\Utils\ObjectUtils;
use Desperado\Framework\Domain\Event\DomainEvent;
use Desperado\Framework\Domain\Event\DomainEventStream;
use Desperado\Framework\Domain\EventSourced\EventSourcedInterface;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Domain\Messages\EventInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Contract\EventSourcedEntryCreatedEvent;
use Desperado\Framework\Infrastructure\EventSourcing\Contract\EventSourcedEntryRestoredEvent;

/**
 * Base event sourced entry
 */
abstract class AbstractEventSourced implements EventSourcedInterface
{
    private const EVENT_APPLY_PREFIX = 'on';

    /**
     * Identity
     *
     * @var IdentityInterface
     */
    private $id;

    /**
     * Aggregate version
     *
     * @var int
     */
    private $version = -1;

    /**
     * Unsaved events
     *
     * @var DomainEvent[]
     */
    private $uncommittedEvents = [];

    /**
     * List of events to be published while saving
     *
     * @var EventInterface[]
     */
    private $toPublishEvents = [];

    /**
     * @inheritdoc
     */
    final public static function fromEventStream(IdentityInterface $identity, DomainEventStream $eventStream): self
    {
        $self = self::restore($identity);

        foreach($eventStream as $event)
        {
            /** @var DomainEvent= $event */
            $self->applyEvent($event);
            $self->version++;
        }

        return $self;
    }

    /**
     * @inheritdoc
     */
    final public function getToPublishEvents(): array
    {
        $events = $this->toPublishEvents;

        $this->resetToPublishEvents();

        return $events;
    }

    /**
     * @inheritdoc
     */
    final public function getEventStream(): DomainEventStream
    {
        $eventStream = DomainEventStream::create($this->uncommittedEvents);

        $this->resetUncommittedEvents();

        return $eventStream;
    }

    /**
     * @inheritdoc
     */
    final public function resetToPublishEvents(): void
    {
        $this->toPublishEvents = [];
    }

    /**
     * @inheritdoc
     */
    final public function resetUncommittedEvents(): void
    {
        $this->uncommittedEvents = [];
    }

    /**
     * @inheritdoc
     */
    final public function getId(): IdentityInterface
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Event sourced entry created callback
     *
     * @return void
     */
    protected function onCreated(): void
    {

    }

    /**
     * Get event sourced entry identity as string representation
     *
     * @return string
     */
    protected function getIdentityAsString(): string
    {
        return $this->getId()->toString();
    }

    /**
     * Raise event
     *
     * @param EventInterface $event
     * @param bool           $publishOnFlush
     *
     * @return void
     */
    final protected function raiseEvent(EventInterface $event, bool $publishOnFlush = true): void
    {
        $messageId = \spl_object_hash($event);

        if(false === isset($this->uncommittedEvents[$messageId]))
        {
            $domainEvent = DomainEvent::new($event, $this->version);
            $this->applyEvent($domainEvent);
            $this->version++;
            $this->uncommittedEvents[] = $domainEvent;

            if(true === $publishOnFlush)
            {
                $this->toPublishEvents[] = $event;
            }
        }
    }

    /**
     * Create new event sourced entry
     *
     * @param IdentityInterface $identity
     *
     * @return AbstractEventSourced
     */
    abstract protected static function new(IdentityInterface $identity);

    /**
     * Apply create event sourced event
     *
     * @param EventSourcedEntryCreatedEvent $event
     *
     * @return void
     */
    final protected function onEventSourcedEntryCreatedEvent(EventSourcedEntryCreatedEvent $event): void
    {
        $this->id = new $event->type($event->id);

        $this->onCreated();
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    final protected function __construct()
    {

    }

    /**
     * Restore from event stream
     *
     * @param IdentityInterface $identity
     *
     * @return AbstractEventSourced
     */
    private static function restore(IdentityInterface $identity): self
    {
        $self = new static();
        $self->id = $identity;

        return $self;
    }

    /**
     * Apply event
     *
     * @param DomainEvent $domainEvent
     *
     * @return void
     */
    private function applyEvent(DomainEvent $domainEvent): void
    {
        $event = $domainEvent->getReceivedEvent();

        $eventClassName = ObjectUtils::getClassName($event);
        $eventListenerMethodName = sprintf('%s%s', self::EVENT_APPLY_PREFIX, $eventClassName);

        if(true === \method_exists($this, $eventListenerMethodName))
        {
            $this->$eventListenerMethodName($event);
        }
    }
}
