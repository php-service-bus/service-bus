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

namespace Desperado\ServiceBus\Index\Storage\Sql;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Index\Storage\IndexesStorage;
use function Desperado\ServiceBus\Storage\fetchOne;
use function Desperado\ServiceBus\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Storage\SQL\updateQuery;
use Desperado\ServiceBus\Storage\StorageAdapter;

/**
 *
 */
final class SqlIndexesStorage implements IndexesStorage
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
     * @inheritDoc
     */
    public function find(string $indexKey, string $valueKey): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $indexKey, string $valueKey) use ($adapter): \Generator
            {
                $query = selectQuery('event_sourcing_indexes')
                    ->where(equalsCriteria('index_tag', $indexKey))
                    ->andWhere(equalsCriteria('value_key', $valueKey))
                    ->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                /** @var array<string, mixed>|null $result */
                $result = yield fetchOne($resultSet);

                unset($resultSet);

                if(null !== $result && true === \is_array($result))
                {
                    return $result['value_data'];
                }
            },
            $indexKey, $valueKey
        );
    }

    /**
     * @inheritDoc
     */
    public function add(string $indexKey, string $valueKey, $value): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
        /** @psalm-suppress MissingClosureParamType */
            static function(string $indexKey, string $valueKey, $value) use ($adapter): \Generator
            {
                $query = insertQuery('event_sourcing_indexes', [
                    'index_tag'  => $indexKey,
                    'value_key'  => $valueKey,
                    'value_data' => $value
                ])->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                unset($resultSet, $query);

            },
            $indexKey, $valueKey, $value
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(string $indexKey, string $valueKey): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $indexKey, string $valueKey) use ($adapter): \Generator
            {
                $deleteQuery = deleteQuery('event_sourcing_indexes')
                    ->where(equalsCriteria('index_tag', $indexKey))
                    ->andWhere(equalsCriteria('value_key', $valueKey));

                $compiledQuery = $deleteQuery->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

                unset($resultSet, $deleteQuery, $compiledQuery);

            }, $indexKey, $valueKey
        );
    }

    /**
     * @inheritDoc
     */
    public function update(string $indexKey, string $valueKey, $value): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
        /** @psalm-suppress MissingClosureParamType */
            static function(string $indexKey, string $valueKey, $value) use ($adapter): \Generator
            {
                $query = updateQuery('event_sourcing_indexes', ['value_data' => $value])
                    ->where(equalsCriteria('index_tag', $indexKey))
                    ->andWhere(equalsCriteria('value_key', $valueKey))
                    ->compile();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $resultSet */
                $resultSet = yield $adapter->execute($query->sql(), $query->params());

                unset($resultSet, $query);

            }, $indexKey, $valueKey, $value
        );
    }
}
