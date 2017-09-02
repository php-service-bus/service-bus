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
