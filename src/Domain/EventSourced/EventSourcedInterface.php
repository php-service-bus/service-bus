<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Domain\EventSourced;

use Desperado\ConcurrencyFramework\Domain\Event\DomainEventStream;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;

/**
 * Event sourced entry
 */
interface EventSourcedInterface
{
    /**
     * Get event sourced entry ID
     *
     * @return IdentityInterface
     */
    public function getId(): IdentityInterface;

    /**
     * Get uncommitted event stream
     *
     * @return DomainEventStream
     */
    public function getEventStream(): DomainEventStream;

    /**
     * Create event sourced entity from event stream history
     *
     * @param IdentityInterface $identity
     * @param DomainEventStream $eventStream
     *
     * @return $this
     */
    public static function fromEventStream(IdentityInterface $identity, DomainEventStream $eventStream);

    /**
     * Reset uncommitted event collection
     *
     * @return void
     */
    public function resetUncommittedEvents(): void;

    /**
     * Get event sourced entry version
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * Get list of events to be published while saving
     *
     * @return EventInterface[]
     */
    public function getToPublishEvents(): array;

    /**
     * Reset events to publish while saving
     *
     * @return void
     */
    public function resetToPublishEvents(): void;
}
