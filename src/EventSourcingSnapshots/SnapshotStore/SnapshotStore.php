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

namespace Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore;

use Desperado\ServiceBus\EventSourcing\AggregateId;

/**
 *
 */
interface SnapshotStore
{
    /**
     * Save snapshot
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param StoredAggregateSnapshot $aggregateSnapshot
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     */
    public function save(StoredAggregateSnapshot $aggregateSnapshot): \Generator;

    /**
     * Load snapshot
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param AggregateId $id
     *
     * @return \Generator<\Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\StoredAggregateSnapshot|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function load(AggregateId $id): \Generator;

    /**
     * Remove snapshot from database
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param AggregateId $id
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function remove(AggregateId $id): \Generator;
}
