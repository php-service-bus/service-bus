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
use Amp\Success;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\StoredSaga;
use function Desperado\ServiceBus\Storage\fetchOne;
use function Desperado\ServiceBus\Storage\SQL\createInsertQuery;
use function Desperado\ServiceBus\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Storage\SQL\updateQuery;
use Desperado\ServiceBus\Storage\StorageAdapter;

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
    public function save(StoredSaga $storedSaga, callable $afterSaveHandler): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredSaga $storedSaga) use ($adapter, $afterSaveHandler): \Generator
            {
                $query = createInsertQuery('sagas_store', [
                    'id'               => $storedSaga->id(),
                    'identifier_class' => $storedSaga->idClass(),
                    'saga_class'       => $storedSaga->sagaClass(),
                    'payload'          => $storedSaga->payload(),
                    'state_id'         => $storedSaga->status(),
                    'created_at'       => $storedSaga->formatCreatedAt(),
                    'expiration_date'  => $storedSaga->formatExpirationDate(),
                    'closed_at'        => $storedSaga->formatClosedAt()
                ])->compile();

                yield $adapter->execute($query->sql(), $query->params());

                yield call($afterSaveHandler);
            },
            $storedSaga
        );
    }

    /**
     * @inheritdoc
     */
    public function update(StoredSaga $storedSaga, callable $afterSaveHandler): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredSaga $storedSaga) use ($adapter, $afterSaveHandler): \Generator
            {
                /** @var \Desperado\ServiceBus\Storage\TransactionAdapter $transaction */
                $transaction = yield $adapter->transaction();

                try
                {
                    /** @psalm-suppress ImplicitToStringCast */
                    $query = updateQuery('sagas_store', [
                        'payload'   => $storedSaga->payload(),
                        'state_id'  => $storedSaga->status(),
                        'closed_at' => $storedSaga->formatClosedAt()
                    ])
                        ->where(equalsCriteria('id', $storedSaga->id()))
                        ->andWhere(equalsCriteria('identifier_class', $storedSaga->idClass()))
                        ->compile();

                    yield $transaction->execute($query->sql(), $query->params());

                    yield call($afterSaveHandler);

                    yield $transaction->commit();
                }
                catch(\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw $throwable;
                }
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
                /** @psalm-suppress ImplicitToStringCast */
                $query = selectQuery('sagas_store')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('identifier_class', \get_class($id)))
                    ->compile();

                $iterator = yield $adapter->execute($query->sql(), $query->params());

                /** @var array|null $result */
                $result = yield fetchOne($iterator);

                if(null !== $result && true === isset($result['payload']))
                {
                    $result['payload'] = $adapter->unescapeBinary($result['payload']);

                    return yield new Success(StoredSaga::fromRow($result));
                }
            },
            $id
        );
    }

    /**
     * @inheritdoc
     */
    public function remove(SagaId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SagaId $id) use ($adapter): \Generator
            {
                /** @psalm-suppress ImplicitToStringCast */
                $query = deleteQuery('sagas_store')
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('identifier_class', \get_class($id)))
                    ->compile();

                yield $adapter->execute($query->sql(), $query->params());
            },
            $id
        );
    }
}
