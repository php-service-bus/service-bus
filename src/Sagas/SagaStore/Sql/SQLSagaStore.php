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

namespace Desperado\ServiceBus\Sagas\SagaStore\Sql;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\StoredSaga;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\updateQuery;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;

/**
 * Sql sagas storage
 */
final class SQLSagaStore implements SagasStore
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
    public function save(StoredSaga $storedSaga): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredSaga $storedSaga) use ($adapter): \Generator
            {
                /** @var \Latitude\QueryBuilder\Query\InsertQuery $insertQuery */
                $insertQuery = insertQuery('sagas_store', [
                    'id'               => $storedSaga->id,
                    'identifier_class' => $storedSaga->idClass,
                    'saga_class'       => $storedSaga->sagaClass,
                    'payload'          => $storedSaga->payload,
                    'state_id'         => $storedSaga->status,
                    'created_at'       => $storedSaga->formatCreatedAt(),
                    'expiration_date'  => $storedSaga->formatExpirationDate(),
                    'closed_at'        => $storedSaga->formatClosedAt()
                ]);

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $insertQuery->compile();

                yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());
            },
            $storedSaga
        );
    }

    /**
     * @inheritdoc
     */
    public function update(StoredSaga $storedSaga): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredSaga $storedSaga) use ($adapter): \Generator
            {
                /**
                 * @var \Latitude\QueryBuilder\Query\UpdateQuery $updateQuery
                 *
                 * @psalm-suppress ImplicitToStringCast
                 */
                $updateQuery = updateQuery('sagas_store', [
                    'payload'   => $storedSaga->payload,
                    'state_id'  => $storedSaga->status,
                    'closed_at' => $storedSaga->formatClosedAt()
                ])
                    ->where(equalsCriteria('id', $storedSaga->id))
                    ->andWhere(equalsCriteria('identifier_class', $storedSaga->idClass));

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $updateQuery->compile();

                yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());
            },
            $storedSaga
        );
    }

    /**
     * @inheritdoc
     */
    public function load(SagaId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SagaId $id) use ($adapter): \Generator
            {
                /**
                 * @var \Latitude\QueryBuilder\Query\SelectQuery $selectQuery
                 *
                 * @psalm-suppress ImplicitToStringCast
                 */
                $selectQuery = selectQuery('sagas_store')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('identifier_class', \get_class($id)));

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $selectQuery->compile();

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

                /** @var array|null $result */
                $result = yield fetchOne($resultSet);

                unset($selectQuery, $compiledQuery, $resultSet);

                if(null !== $result && true === isset($result['payload']))
                {
                    /**
                     * @var array{
                     *     id:string,
                     *     identifier_class:string,
                     *     saga_class:string,
                     *     payload:string,
                     *     state_id:string,
                     *     created_at:string,
                     *     expiration_date:string,
                     *     closed_at:string|null
                     * } $result
                     *
                     * @psalm-suppress MixedArgument
                     */

                    $result['payload'] = $adapter->unescapeBinary($result['payload']);

                    return StoredSaga::fromRow($result);
                }
            },
            $id
        );
    }

    /**
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @inheritdoc
     */
    public function remove(SagaId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SagaId $id) use ($adapter): \Generator
            {
                /**
                 * @var \Latitude\QueryBuilder\Query\DeleteQuery $deleteQuery
                 *
                 * @psalm-suppress ImplicitToStringCast
                 */
                $deleteQuery = deleteQuery('sagas_store')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('identifier_class', \get_class($id)));

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $deleteQuery->compile();

                return yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());
            },
            $id
        );
    }
}
