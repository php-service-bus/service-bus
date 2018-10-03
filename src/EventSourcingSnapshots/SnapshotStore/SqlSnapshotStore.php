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
use Desperado\ServiceBus\EventSourcing\AggregateId;
use function Desperado\ServiceBus\Storage\fetchOne;
use function Desperado\ServiceBus\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Storage\SQL\selectQuery;
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

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredAggregateSnapshot $aggregateSnapshot) use ($adapter): \Generator
            {
                $query = insertQuery('event_store_snapshots', [
                    'id'                 => $aggregateSnapshot->aggregateId(),
                    'aggregate_id_class' => $aggregateSnapshot->aggregateIdClass(),
                    'aggregate_class'    => $aggregateSnapshot->aggregateClass(),
                    'version'            => $aggregateSnapshot->version(),
                    'payload'            => $aggregateSnapshot->payload(),
                    'created_at'         => $aggregateSnapshot->createdAt()
                ])->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                unset($query, $resultSet);
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

                /** @psalm-suppress ImplicitToStringCast */
                $query = selectQuery('event_store_snapshots')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('aggregate_id_class', \get_class($id)))
                    ->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                /** @var array|null $data */
                $data = yield fetchOne($resultSet);

                if(true === \is_array($data) && 0 !== \count($data))
                {
                    $data['payload'] = \stream_get_contents($data['payload'], -1, 0);

                    $storedSnapshot = new StoredAggregateSnapshot(
                        $data['id'],
                        $data['aggregate_id_class'],
                        $data['aggregate_class'],
                        (int) $data['version'],
                        $adapter->unescapeBinary($data['payload']),
                        $data['created_at']
                    );
                }

                unset($resultSet, $data, $query);

                return $storedSnapshot;
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
                /** @psalm-suppress ImplicitToStringCast */
                $query = deleteQuery('event_store_snapshots')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('aggregate_id_class', \get_class($id)))
                    ->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                unset($resultSet, $query);
            },
            $id
        );
    }
}
