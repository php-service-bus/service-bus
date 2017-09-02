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

namespace Desperado\ConcurrencyFramework\Domain\EventStore;

use Desperado\ConcurrencyFramework\Domain\Event\DomainEventStream;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;

/**
 * Event store
 */
interface EventStoreInterface
{
    /**
     * Load event stream by ID
     *
     * @param IdentityInterface $id
     *
     * @return DomainEventStream|null
     */
    public function load(IdentityInterface $id): ?DomainEventStream;

    /**
     * Load event stream by ID and specified version
     *
     * @param IdentityInterface $id
     * @param int               $playhead
     *
     * @return DomainEventStream|null
     */
    public function loadFromPlayhead(IdentityInterface $id, int $playhead): ?DomainEventStream;

    /**
     * Append events
     *
     * @param IdentityInterface $id
     * @param DomainEventStream $eventStream
     *
     * @return void
     */
    public function append(IdentityInterface $id, DomainEventStream $eventStream): void;
}
