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

namespace Desperado\ServiceBus\Scheduler\Store\Sql;

use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use Amp\Success;
use function Desperado\ServiceBus\Common\datetimeInstantiator;
use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use Desperado\ServiceBus\Scheduler\Store\SchedulerStore;
use function Desperado\ServiceBus\Common\datetimeToString;
use function Desperado\ServiceBus\Storage\fetchOne;
use function Desperado\ServiceBus\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Storage\SQL\selectQuery;
use Desperado\ServiceBus\Storage\StorageAdapter;

/**
 *
 */
final class SqlSchedulerStore implements SchedulerStore
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
    public function add(ScheduledOperation $operation): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperation $operation) use ($adapter): \Generator
            {
                /** @psalm-suppress ImplicitToStringCast */
                $query = insertQuery('scheduler_registry', [
                    'id'              => (string) $operation->id(),
                    'processing_date' => datetimeToString($operation->date()),
                    'command'         => \base64_encode(\serialize($operation->command()))
                ])->compile();

                yield $adapter->execute($query->sql(), $query->params());
            },
            $operation
        );
    }

    /**
     * @inheritdoc
     */
    public function extract(ScheduledOperationId $id, callable $postExtractCallback): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id, callable $postExtractCallback) use ($adapter): \Generator
            {
                /** @psalm-suppress ImplicitToStringCast */
                $query = selectQuery('scheduler_registry')
                    ->where(equalsCriteria('id', $id))
                    ->compile();

                /** @var array|null $result */
                $result = yield fetchOne(
                    yield $adapter->execute($query->sql(), $query->params())
                );

                /** Scheduled operation not found */
                if(false === \is_array($result) || 0 === \count($result))
                {
                    return yield new Success(null);
                }

                $result['command'] = $adapter->unescapeBinary($result['command']);

                $operation = ScheduledOperation::fromRow($result);

                /** @var \Desperado\ServiceBus\Storage\TransactionAdapter $transaction */
                $transaction = yield $adapter->transaction();

                try
                {
                    /** @psalm-suppress ImplicitToStringCast */
                    $deleteQuery = deleteQuery('scheduler_registry')
                        ->where(equalsCriteria('id', $id))
                        ->compile();

                    yield $transaction->execute($deleteQuery->sql(), $deleteQuery->params());

                    asyncCall($postExtractCallback, $operation);

                    yield $transaction->commit();

                    return yield new Success($operation);
                }
                catch(\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw $throwable;
                }
            },
            $id, $postExtractCallback
        );
    }

    /**
     * @inheritdoc
     */
    public function loadNextOperation(): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function() use ($adapter): \Generator
            {
                $result = null;

                $query = selectQuery('scheduler_registry')
                    ->orderBy('processing_date', 'ASC')
                    ->limit(1)
                    ->compile();

                /** @var array|null $result */
                $result = yield fetchOne(
                    yield $adapter->execute($query->sql(), $query->params())
                );

                if(true === \is_array($result) && 0 !== \count($result))
                {
                    /** @var \DateTimeImmutable $datetime */
                    $datetime = datetimeInstantiator($result['processing_date']);

                    $result = new NextScheduledOperation(
                        new ScheduledOperationId($result['id']),
                        $datetime
                    );
                }

                return yield new Success($result);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function remove(ScheduledOperationId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id) use ($adapter): \Generator
            {
                /** @psalm-suppress ImplicitToStringCast */
                $query = deleteQuery('scheduler_registry')
                    ->where(equalsCriteria('id', $id))
                    ->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                return yield new Success(0 !== $resultSet->rowsCount());
            },
            $id
        );
    }
}
