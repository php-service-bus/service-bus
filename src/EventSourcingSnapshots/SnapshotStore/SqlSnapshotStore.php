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
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;

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
                /** @var \Latitude\QueryBuilder\Query\InsertQuery $query */
                $insertQuery = insertQuery('event_store_snapshots', [
                    'id'                 => $aggregateSnapshot->aggregateId,
                    'aggregate_id_class' => $aggregateSnapshot->aggregateIdClass,
                    'aggregate_class'    => $aggregateSnapshot->aggregateClass,
                    'version'            => $aggregateSnapshot->version,
                    'payload'            => \base64_encode($aggregateSnapshot->payload),
                    'created_at'         => $aggregateSnapshot->createdAt
                ]);

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $insertQuery->compile();

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

                unset($insertQuery, $compiledQuery, $resultSet);
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

                /**
                 * @var \Latitude\QueryBuilder\Query\SelectQuery $query
                 *
                 * @psalm-suppress ImplicitToStringCast
                 */
                $selectQuery = selectQuery('event_store_snapshots')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('aggregate_id_class', \get_class($id)));

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $selectQuery->compile();

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

                /** @var array<string, string>|null $data */
                $data = yield fetchOne($resultSet);

                if(true === \is_array($data) && 0 !== \count($data))
                {
                    $storedSnapshot = new StoredAggregateSnapshot(
                        $data['id'],
                        $data['aggregate_id_class'],
                        $data['aggregate_class'],
                        (int) $data['version'],
                        \base64_decode($adapter->unescapeBinary($data['payload'])),
                        $data['created_at']
                    );
                }

                unset($resultSet, $data, $selectQuery, $compiledQuery);

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
                /**
                 * @var \Latitude\QueryBuilder\Query\DeleteQuery $deleteQuery
                 *
                 * @psalm-suppress ImplicitToStringCast
                 */
                $deleteQuery = deleteQuery('event_store_snapshots')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('aggregate_id_class', \get_class($id)));

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $deleteQuery->compile();

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

                unset($resultSet, $compiledQuery, $deleteQuery);
            },
            $id
        );
    }
}
