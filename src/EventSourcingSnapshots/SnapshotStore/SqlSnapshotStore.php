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

use function Amp\call;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\EventSourcing\AggregateId;
use function Desperado\ServiceBus\Storage\fetchOne;
use Desperado\ServiceBus\Storage\StorageAdapter;

/**
 * Snapshots storage
 */
final class SqlSnapshotStore implements SnapshotStore
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @param StorageAdapter $adapter
     */
    public function __construct(StorageAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     */
    public function save(StoredAggregateSnapshot $aggregateSnapshot): Promise
    {
        $adapter = $this->adapter;
        $sql     = 'INSERT INTO event_store_snapshots (id, aggregate_id_class, aggregate_class, version, payload, created_at) VALUES (?, ?, ?, ?, ?, ?)';

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredAggregateSnapshot $aggregateSnapshot) use ($adapter, $sql): \Generator
            {
                yield $adapter->execute($sql, [
                    $aggregateSnapshot->aggregateId(),
                    $aggregateSnapshot->aggregateIdClass(),
                    $aggregateSnapshot->aggregateClass(),
                    $aggregateSnapshot->version(),
                    $aggregateSnapshot->payload(),
                    $aggregateSnapshot->createdAt()
                ]);

                return yield new Success();
            },
            $aggregateSnapshot
        );
    }

    /**
     * @inheritdoc
     */
    public function load(AggregateId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(AggregateId $id) use ($adapter): \Generator
            {
                $storedSnapshot = null;

                /** @var array|null $data */
                $data = yield fetchOne(
                    yield $adapter->execute(
                        'SELECT * FROM event_store_snapshots WHERE id = ? AND aggregate_id_class = ?', [
                            (string) $id,
                            \get_class($id)
                        ]
                    )
                );

                if(null !== $data)
                {
                    $storedSnapshot = new StoredAggregateSnapshot(
                        $data['id'],
                        $data['aggregate_id_class'],
                        $data['aggregate_class'],
                        $data['version'],
                        $adapter->unescapeBinary($data['payload']),
                        $data['created_at']
                    );
                }

                return yield new Success($storedSnapshot);
            },
            $id
        );
    }

    /**
     * @inheritdoc
     */
    public function remove(AggregateId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(AggregateId $id) use ($adapter): \Generator
            {
                yield $adapter->execute(
                /** @lang text */
                    'DELETE FROM event_store_snapshots WHERE id = ? AND aggregate_id_class = ?', [
                        (string) $id,
                        \get_class($id)
                    ]
                );

                return yield new Success();
            },
            $id
        );
    }
}
