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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage;

use Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;

/**
 * Event storage backend
 */
interface EventStorageInterface
{
    /**
     * Save event stream
     *
     * @param StoredEventStream $storedEventStream
     *
     * @return void
     *
     * @throws DuplicatePlayheadException
     */
    public function save(StoredEventStream $storedEventStream): void;

    /**
     * Load stored stream data
     *
     * @param IdentityInterface $id
     *
     * @return array
     */
    public function load(IdentityInterface $id): array;

    /**
     * Load stored stream data from specified version
     *
     * @param IdentityInterface $id
     * @param int               $playheadPosition
     *
     * @return array
     */
    public function loadFromPlayhead(IdentityInterface $id, int $playheadPosition): array;
}
