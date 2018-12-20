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

namespace Desperado\ServiceBus\Infrastructure\Storage\SQL\ActiveRecord;

use function Amp\call;
use Amp\Coroutine;
use Amp\Promise;
use Amp\Success;
use function Desperado\ServiceBus\Common\uuid;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchAll;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use Desperado\ServiceBus\Infrastructure\Storage\QueryExecutor;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\updateQuery;

/**
 * @api
 * @todo: pk generation strategy
 */
abstract class Table
{
    /**
     * Stored entry identifier
     *
     * @var string|null
     */
    private $insertId;

    /**
     * @var QueryExecutor
     */
    private $queryExecutor;

    /**
     * Data collection
     *
     * @var array<string, string|int|float|null>
     */
    private $data = [];

    /**
     * New record flag
     *
     * @var bool
     */
    private $isNew = true;

    /**
     * Data change list
     *
     * @var array<string, string|int|float|null>
     */
    private $changes = [];

    /**
     * Columns info
     *
     * [
     *   'id'    => 'uuid',
     *   'title' => 'varchar'
     * ]
     *
     * @var array<string, string>
     */
    private $columns = [];

    /**
     * Receive associated table name
     *
     * @return string
     */
    abstract protected static function tableName(): string;

    /**
     * Receive primary key name
     *
     * @return string
     */
    protected static function primaryKey(): string
    {
        return 'id';
    }

    /**
     * Create and persist entry
     *
     * @psalm-return \Amp\Promise
     *
     * @param QueryExecutor                        $queryExecutor
     * @param array<string, string|int|float|null> $data
     *
     * @return Promise<\Desperado\ServiceBus\Infrastructure\Storage\SQL\ActiveRecord\Table>
     *
     * @throws \LogicException Unknown attribute
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    final public static function new(QueryExecutor $queryExecutor, array $data): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(array $data) use ($queryExecutor): \Generator
            {
                /**
                 * @var array<string, string|int|float|null> $data
                 * @var static                               $self
                 */

                $self = yield from static::create($queryExecutor, $data, true);

                /** @var string|int $result */
                $result = yield $self->save();

                $self->insertId = (string) $result;

                return $self;
            },
            $data
        );
    }

    /**
     * Find entry by primary key
     *
     * @psalm-return \Amp\Promise
     *
     * @param QueryExecutor $queryExecutor
     * @param int|string    $id
     *
     * @return Promise<static|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    final public static function find(QueryExecutor $queryExecutor, $id): Promise
    {
        return self::findOneBy($queryExecutor, [equalsCriteria(static::primaryKey(), $id)]);
    }

    /**
     * Find one entry by specified criteria
     *
     * @psalm-return \Amp\Promise
     *
     * @param QueryExecutor                                          $queryExecutor
     * @param array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
     *
     * @return Promise<static|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation  result
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\OneResultExpected The result must contain only 1 row
     */
    final public static function findOneBy(QueryExecutor $queryExecutor, array $criteria): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(QueryExecutor $queryExecutor, array $criteria): \Generator
            {
                /**
                 * @var array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
                 * @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet
                 */
                $resultSet = yield from find($queryExecutor, static::tableName(), $criteria);

                /** @var array<string, string|int|float|null>|null $data */
                $data = yield fetchOne($resultSet);

                unset($resultSet);

                if(true === \is_array($data))
                {
                    return yield from self::create($queryExecutor, $data, false);
                }
            },
            $queryExecutor, $criteria
        );
    }

    /**
     * Find entries by specified criteria
     *
     * @psalm-return \Amp\Promise
     *
     * @param QueryExecutor                                          $queryExecutor
     * @param array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
     * @param int|null                                               $limit
     * @param array<string, string>                                  $orderBy
     *
     * @return Promise<array<int, static>>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation  result
     */
    final public static function findBy(
        QueryExecutor $queryExecutor,
        array $criteria = [],
        ?int $limit = null,
        array $orderBy = []
    ): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(QueryExecutor $queryExecutor, array $criteria, ?int $limit, array $orderBy): \Generator
            {
                /**
                 * @var array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
                 * @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet
                 * @var array<string, string>                                  $orderBy
                 */
                $resultSet = yield from find($queryExecutor, static::tableName(), $criteria, $limit, $orderBy);

                /** @var array<string, string|int|float|null>|null $rows */
                $rows = yield fetchAll($resultSet);

                unset($resultSet);

                /** @var array<int, static> $result */
                $result = [];

                if(null !== $rows)
                {
                    /** @var array<string, string|int|float|null> $row */
                    foreach($rows as $row)
                    {
                        /** @var static $entry */
                        $entry    = yield from self::create($queryExecutor, $row, false);
                        $result[] = $entry;

                        unset($entry);
                    }
                }

                return $result;
            },
            $queryExecutor, $criteria, $limit, $orderBy
        );
    }

    /**
     * Save entry changes
     *
     * @psalm-return \Amp\Promise
     *
     * @return Promise<string|int> Returns the ID of the saved entry, or the number of affected rows (in the case of an update)
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed Duplicate entry
     */
    final public function save(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(bool $isNew): \Generator
            {
                /** Store new entry */
                if(true === $isNew)
                {
                    $this->changes = [];

                    /** @var int|string $lastInsertId */
                    $lastInsertId = yield from $this->storeNewEntry($this->data);
                    $this->isNew  = false;

                    return $lastInsertId;
                }

                $changeSet = $this->changes;

                if(0 === \count($changeSet))
                {
                    return 0;
                }

                /** @var int $affectedRows */
                $affectedRows  = yield from $this->updateExistsEntry($changeSet);
                $this->changes = [];

                return $affectedRows;
            },
            $this->isNew
        );
    }

    /**
     * Refresh entry data
     *
     * @psalm-return \Amp\Promise
     *
     * @return Promise
     *
     * @throws \RuntimeException Unable to find an entry (possibly RC occured)
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    public function refresh(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield from find(
                    $this->queryExecutor,
                    static::tableName(),
                    [equalsCriteria(static::primaryKey(), $this->searchPrimaryKeyValue())]
                );

                /** @var array<string, string|int|float|null>|null $row */
                $row = yield fetchOne($resultSet);

                unset($resultSet);

                if(true === \is_array($row))
                {
                    $this->changes = [];
                    $this->data    = unescapeBinary($this->queryExecutor, $row);

                    return;
                }

                throw new \RuntimeException('Failed to update entity: data has been deleted');
            }
        );
    }

    /**
     * Delete entry
     *
     * @return Promise Does not return result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    final public function remove(): Promise
    {
        if(true === $this->isNew)
        {
            return new Success();
        }

        return new Coroutine(
            remove(
                $this->queryExecutor,
                static::tableName(),
                [equalsCriteria(static::primaryKey(), $this->searchPrimaryKeyValue())]
            )
        );
    }

    /**
     * Receive the ID of the last entry added
     *
     * @return string|null
     */
    final public function lastInsertId(): ?string
    {
        return $this->insertId;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array<string, mixed>
     */
    final public function __debugInfo(): array
    {
        return [
            'data'    => $this->data,
            'isNew'   => $this->isNew,
            'changes' => $this->changes,
            'columns' => $this->columns
        ];
    }

    /**
     * @param string                $name
     * @param int|string|float|null $value
     *
     * @return void
     *
     * @throws \LogicException Unknown column
     */
    final public function __set(string $name, $value): void
    {
        if(true === isset($this->columns[$name]))
        {
            $this->data[$name]    = $value;
            $this->changes[$name] = $value;

            return;
        }

        throw new \LogicException(
            \sprintf(
                'Column "%s" does not exist in table "%s"',
                $name, static::tableName()
            )
        );
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final public function __isset(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * @param string $name
     *
     * @return int|string|float|null
     */
    final public function __get(string $name)
    {
        return $this->data[$name];
    }

    /**
     * Receive query execution handler
     *
     * @return QueryExecutor
     */
    final protected function queryExecutor(): QueryExecutor
    {
        return $this->queryExecutor;
    }

    /**
     * Store new entry
     *
     * @psalm-return \Generator
     *
     * @param array<string, string|int|float|null> $changeSet
     *
     * @return \Generator<string|int>
     */
    private function storeNewEntry(array $changeSet): \Generator
    {
        $primaryKey = static::primaryKey();

        if(false === \array_key_exists($primaryKey, $changeSet) && 'uuid' === \strtolower($this->columns[$primaryKey]))
        {
            $changeSet[$primaryKey] = uuid();
        }

        $queryBuilder = insertQuery(static::tableName(), $changeSet);

        /** @todo: fix me */
        if($this->queryExecutor instanceof AmpPostgreSQLAdapter)
        {
            /**
             * @psalm-suppress UndefinedMethod Cannot find method in traits
             * @var \Latitude\QueryBuilder\Query\Postgres\InsertQuery $queryBuilder
             */
            $queryBuilder->returning($primaryKey);
        }

        $compiledQuery = $queryBuilder->compile();

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
        $resultSet = yield $this->queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());

        $insertedEntryId = $resultSet->lastInsertId();

        unset($queryBuilder, $compiledQuery, $resultSet);

        if(false === isset($this->data[$primaryKey]))
        {
            $this->data[$primaryKey] = $insertedEntryId;
        }

        return $insertedEntryId;
    }

    /**
     * Update exists entry
     *
     * @psalm-return \Generator
     *
     * @param array $changeSet
     *
     * @return \Generator<int>
     */
    private function updateExistsEntry(array $changeSet): \Generator
    {
        /**
         * @var string                               $query
         * @var array<string, string|int|float|null> $parameters
         */
        [$query, $parameters] = buildQuery(
            updateQuery(static::tableName(), $changeSet),
            [equalsCriteria(static::primaryKey(), $this->searchPrimaryKeyValue())]
        );

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
        $resultSet = yield $this->queryExecutor->execute($query, $parameters);

        $this->changes = [];
        $affectedRows  = $resultSet->affectedRows();

        unset($query, $parameters, $resultSet);

        return $affectedRows;
    }

    /**
     * @return string
     *
     * @throws \LogicException Unable to find primary key value
     */
    private function searchPrimaryKeyValue(): string
    {
        $primaryKey = static::primaryKey();

        if(true === isset($this->data[$primaryKey]) && '' !== (string ) $this->data[$primaryKey])
        {
            return (string) $this->data[$primaryKey];
        }

        throw new \LogicException(
            \sprintf(
                'In the parameters of the entity must be specified element with the index "%s" (primary key)',
                $primaryKey
            )
        );
    }

    /**
     * Create entry
     *
     * @psalm-return \Generator
     *
     * @param QueryExecutor                        $queryExecutor
     * @param array<string, string|int|float|null> $data
     * @param bool                                 $isNew
     *
     * @return \Generator<static>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    private static function create(QueryExecutor $queryExecutor, array $data, bool $isNew): \Generator
    {
        $metadataExtractor = new TableMetadataLoader($queryExecutor);

        $self = new static($queryExecutor);

        /** @var array<string, string> $columns */
        $columns = yield $metadataExtractor->columns(static::tableName());

        $self->columns = $columns;

        if(false === $isNew)
        {
            $data = unescapeBinary($queryExecutor, $data);
        }

        foreach($data as $key => $value)
        {
            $self->{$key} = $value;
        }

        $self->isNew = $isNew;

        return $self;
    }

    /**
     * @param QueryExecutor $queryExecutor
     */
    private function __construct(QueryExecutor $queryExecutor)
    {
        $this->queryExecutor = $queryExecutor;
    }
}
