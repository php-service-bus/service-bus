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

namespace Desperado\Framework\Domain\EventStore;

use Desperado\Framework\Domain\Event\DomainEventStream;
use Desperado\Framework\Domain\Identity\IdentityInterface;

/**
 * Event store
 */
interface EventStoreInterface
{
    /**
     * Load event stream by ID
     *
     * @param IdentityInterface $id
     * @param callable          $onLoaded function(DomainEventStream $eventStream = null) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function load(IdentityInterface $id, callable $onLoaded, callable $onFailed = null): void;

    /**
     * Load event stream by ID and specified version
     *
     * @param IdentityInterface $id
     * @param int               $playhead
     * @param callable          $onLoaded function(DomainEventStream $eventStream = null) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function loadFromPlayhead(
        IdentityInterface $id,
        int $playhead,
        callable $onLoaded,
        callable $onFailed = null
    ): void;

    /**
     * Append events
     *
     * @param IdentityInterface $id
     * @param DomainEventStream $eventStream
     * @param callable|null     $onSaved  function() {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function append(
        IdentityInterface $id,
        DomainEventStream $eventStream,
        callable $onSaved = null,
        callable $onFailed = null
    ): void;
}
