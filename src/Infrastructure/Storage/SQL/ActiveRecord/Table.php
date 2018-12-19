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
use Amp\Promise;
use Amp\Success;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\Storage\BinaryDataDecoder;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchAll;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use Desperado\ServiceBus\Infrastructure\Storage\QueryExecutor;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\updateQuery;
use Latitude\QueryBuilder\CriteriaInterface;
use Latitude\QueryBuilder\Query as LatitudeQuery;

/**
 * @todo: pk generation strategy
 */
abstract class Table
{
    /**
     * Stored entry identifier
     *
     * @var string|int
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
     * @param QueryExecutor $queryExecutor
     * @param array         $data
     *
     * @return Promise<static>
     *
     * @throws \LogicException Unknown attribute
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    final public static function new(QueryExecutor $queryExecutor, array $data): Promise
    {
        return call(
            function(array $data) use ($queryExecutor): \Generator
            {
                /** @var self $self */
                $self = yield from static::create($queryExecutor, $data, true);

                $self->insertId = (string) yield $self->save();

                return $self;
            },
            $data
        );
    }

    /**
     * Find entry by primary key
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
     * @param QueryExecutor                                        $queryExecutor
     * @param array<int, \Latitude\QueryBuilder\CriteriaInterface> $criteria
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
                [$query, $parameters] = self::buildQuery(selectQuery(static::tableName()), $criteria);

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $queryExecutor->execute($query, $parameters);

                /** @var array<string, string|int|float|null>|null $data */
                $data = yield fetchOne($resultSet);

                unset($query, $parameters, $resultSet);

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
     * @param QueryExecutor                                        $queryExecutor
     * @param array<int, \Latitude\QueryBuilder\CriteriaInterface> $criteria
     * @param int                                                  $limit
     * @param array|null                                           $orderBy
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
        int $limit = 50,
        ?array $orderBy = null
    ): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(QueryExecutor $queryExecutor, array $criteria, int $limit, ?array $orderBy): \Generator
            {
                [$query, $parameters] = self::buildQuery(
                    selectQuery(static::tableName()),
                    $criteria,
                    $orderBy ?? [],
                    $limit
                );

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $queryExecutor->execute($query, $parameters);

                /** @var array<string, string|int|float|null>|null $rows */
                $rows = yield fetchAll($resultSet);

                unset($query, $parameters, $resultSet);

                $result = [];

                foreach($rows as $row)
                {
                    $result[] = yield from self::create($queryExecutor, $row, false);
                }

                return $result;
            },
            $queryExecutor, $criteria, $limit, $orderBy
        );
    }

    /**
     * Save entry changes
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
                [$query, $parameters] = self::buildQuery(
                    selectQuery(static::tableName()),
                    [equalsCriteria(static::primaryKey(), $this->searchPrimaryKeyValue())]
                );

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $this->queryExecutor->execute($query, $parameters);

                /** @var array<string, string|int|float|null>|null $row */
                $row = yield fetchOne($resultSet);

                if(true === \is_array($row))
                {
                    $this->changes = [];
                    $this->data    = self::unescapeBinary($this->queryExecutor, $row);

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
        $queryExecutor = $this->queryExecutor;

        if(true === $this->isNew)
        {
            return new Success();
        }

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function() use ($queryExecutor): \Generator
            {
                [$query, $parameters] = self::buildQuery(deleteQuery(static::tableName()));

                /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
                $resultSet = yield $queryExecutor->execute($query, $parameters);

                $affectedRows = $resultSet->affectedRows();

                unset($resultSet, $query, $parameters);

                return $affectedRows;
            }
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
            /** @var \Latitude\QueryBuilder\Query\Postgres\InsertQuery $queryBuilder */
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
     * @param array $changeSet
     *
     * @return \Generator<int>
     */
    private function updateExistsEntry(array $changeSet): \Generator
    {
        [$query, $parameters] = self::buildQuery(
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
     * @return string|int
     *
     * @throws \LogicException Unable to find primary key value
     */
    private function searchPrimaryKeyValue()
    {
        $primaryKey = static::primaryKey();

        if(true === isset($this->data[$primaryKey]) && '' !== (string ) $this->data[$primaryKey])
        {
            return $this->data[$primaryKey];
        }

        throw new \LogicException(
            \sprintf(
                'In the parameters of the entity must be specified element with the index "%s" (primary key)',
                $primaryKey
            )
        );
    }

    /**
     * @param LatitudeQuery\AbstractQuery $queryBuilder
     * @param array                       $criteriaCollection
     * @param array                       $orderBy
     * @param int|null                    $limit
     *
     * @return array 0 - SQL query; 1 - query parameters
     */
    private static function buildQuery(
        LatitudeQuery\AbstractQuery $queryBuilder,
        array $criteriaCollection = [],
        array $orderBy = [],
        ?int $limit = null
    ): array
    {
        /** @var LatitudeQuery\SelectQuery|LatitudeQuery\UpdateQuery|LatitudeQuery\DeleteQuery $queryBuilder */

        $isFirstCondition = true;

        /** @var CriteriaInterface $criteriaItem */
        foreach($criteriaCollection as $criteriaItem)
        {
            $methodName = true === $isFirstCondition ? 'where' : 'andWhere';
            $queryBuilder->{$methodName}($criteriaItem);
            $isFirstCondition = false;
        }

        if(null !== $limit)
        {
            $queryBuilder->limit($limit);
        }

        foreach($orderBy as $column => $direction)
        {
            $queryBuilder->orderBy($column, $direction);
        }

        $compiledQuery = $queryBuilder->compile();

        return [
            $compiledQuery->sql(),
            $compiledQuery->params()
        ];
    }

    /**
     * Create entry
     *
     * @param QueryExecutor                        $queryExecutor
     * @param array<string, string|int|float|null> $data
     * @param bool                                 $isNew
     *
     * @return \Generator
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
            $data = self::unescapeBinary($queryExecutor, $data);
        }

        foreach($data as $key => $value)
        {
            $self->{$key} = $value;
        }

        $self->isNew = $isNew;

        return $self;
    }

    /**
     * Unescape binary data
     *
     * @param QueryExecutor $queryExecutor
     * @param array         $set
     *
     * @return array<string, string|int|null|float>
     */
    private static function unescapeBinary(QueryExecutor $queryExecutor, array $set): array
    {
        if($queryExecutor instanceof BinaryDataDecoder)
        {
            foreach($set as $key => $value)
            {
                if(false === empty($value) && true === \is_string($value))
                {
                    $set[$key] = $queryExecutor->unescapeBinary($value);
                }
            }
        }

        return $set;
    }

    /**
     * @param QueryExecutor $queryExecutor
     */
    private function __construct(QueryExecutor $queryExecutor)
    {
        $this->queryExecutor = $queryExecutor;
    }
}
