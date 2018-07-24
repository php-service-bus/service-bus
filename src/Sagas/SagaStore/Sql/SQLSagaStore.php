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
                yield $adapter->execute(
                    'INSERT INTO sagas_store (id, identifier_class, saga_class, payload, state_id, created_at, closed_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [
                        $storedSaga->id(),
                        $storedSaga->idClass(),
                        $storedSaga->sagaClass(),
                        $storedSaga->payload(),
                        $storedSaga->status(),
                        $storedSaga->formatCreatedAt(),
                        $storedSaga->formatClosedAt()
                    ]
                );

                yield call($afterSaveHandler);

                return yield new Success();
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
                    yield $transaction->execute(
                        'UPDATE sagas_store SET payload = ?, state_id = ?, closed_at = ? WHERE id = ? AND identifier_class = ?', [
                            $storedSaga->payload(),
                            $storedSaga->status(),
                            $storedSaga->formatClosedAt(),
                            $storedSaga->id(),
                            $storedSaga->idClass()
                        ]
                    );

                    yield call($afterSaveHandler);

                    yield $transaction->commit();

                    return yield new Success();
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
                $iterator = yield $adapter->execute(
                    'SELECT * FROM sagas_store WHERE id = ? AND identifier_class = ?', [
                        (string) $id,
                        \get_class($id)
                    ]
                );

                /** @var array|null $result */
                $result = yield fetchOne($iterator);

                if(null !== $result && true === isset($result['payload']))
                {
                    $result['payload'] = $adapter->unescapeBinary($result['payload']);

                    return yield new Success(StoredSaga::fromRow($result));
                }

                return yield new Success();
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
                yield $adapter->execute(
                /** @lang text */
                    'DELETE FROM sagas_store WHERE id = ? AND identifier_class = ?', [
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
