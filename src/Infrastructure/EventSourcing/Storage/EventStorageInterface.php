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

namespace Desperado\Framework\Infrastructure\EventSourcing\Storage;

use Desperado\Framework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\Framework\Domain\Identity\IdentityInterface;

/**
 * Event storage backend
 */
interface EventStorageInterface
{
    /**
     * Save event stream
     *
     * @param StoredEventStream $storedEventStream
     * @param callable|null     $onSaved  function() {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function save(StoredEventStream $storedEventStream, callable $onSaved = null, callable $onFailed = null): void;

    /**
     * Load stored stream data
     *
     * @param IdentityInterface $id
     * @param callable          $onLoaded function(array $data = []) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function load(IdentityInterface $id, callable $onLoaded, callable $onFailed = null): void;

    /**
     * Load stored stream data from specified version
     *
     * @param IdentityInterface $id
     * @param int               $playheadPosition
     * @param callable          $onLoaded function(array $data = []) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function loadFromPlayhead(
        IdentityInterface $id,
        int $playheadPosition,
        callable $onLoaded,
        callable $onFailed = null
    ): void;
}
