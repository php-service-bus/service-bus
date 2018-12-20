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

use Desperado\ServiceBus\Infrastructure\Storage\BinaryDataDecoder;
use Desperado\ServiceBus\Infrastructure\Storage\QueryExecutor;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\deleteQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use Latitude\QueryBuilder\Query as LatitudeQuery;

/**
 * @internal
 *
 * @psalm-return \Generator
 *
 * @param QueryExecutor                                          $queryExecutor
 * @param string                                                 $tableName
 * @param array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
 * @param int|null                                               $limit
 * @param array<string, string>                                  $orderBy
 *
 * @return \Generator<\Desperado\ServiceBus\Infrastructure\Storage\ResultSet>
 *
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
 */
function find(QueryExecutor $queryExecutor, string $tableName, array $criteria = [], ?int $limit = null, array $orderBy = []): \Generator
{
    /**
     * @var string                               $query
     * @var array<string, string|int|float|null> $parameters
     */
    [$query, $parameters] = buildQuery(selectQuery($tableName), $criteria, $orderBy, $limit);

    return yield $queryExecutor->execute($query, $parameters);
}

/**
 * @internal
 *
 * @psalm-return \Generator
 *
 * @param QueryExecutor                                          $queryExecutor
 * @param string                                                 $tableName
 * @param array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
 *
 * @return \Generator<int>
 *
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
 */
function remove(QueryExecutor $queryExecutor, string $tableName, array $criteria = []): \Generator
{
    /**
     * @var string                               $query
     * @var array<string, string|int|float|null> $parameters
     */
    [$query, $parameters] = buildQuery(deleteQuery($tableName), $criteria);

    /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
    $resultSet = yield $queryExecutor->execute($query, $parameters);

    $affectedRows = $resultSet->affectedRows();

    unset($resultSet);

    return $affectedRows;
}

/**
 * @internal
 *
 * @param LatitudeQuery\AbstractQuery                            $queryBuilder
 * @param array<mixed, \Latitude\QueryBuilder\CriteriaInterface> $criteria
 * @param array<string, string>                                  $orderBy
 * @param int|null                                               $limit
 *
 * @return array 0 - SQL query; 1 - query parameters
 */
function buildQuery(
    LatitudeQuery\AbstractQuery $queryBuilder,
    array $criteria = [],
    array $orderBy = [],
    ?int $limit = null
): array
{
    /** @var LatitudeQuery\SelectQuery|LatitudeQuery\UpdateQuery|LatitudeQuery\DeleteQuery $queryBuilder */

    $isFirstCondition = true;

    /** @var \Latitude\QueryBuilder\CriteriaInterface $criteriaItem */
    foreach($criteria as $criteriaItem)
    {
        $methodName = true === $isFirstCondition ? 'where' : 'andWhere';
        $queryBuilder->{$methodName}($criteriaItem);
        $isFirstCondition = false;
    }

    if($queryBuilder instanceof LatitudeQuery\SelectQuery)
    {
        foreach($orderBy as $column => $direction)
        {
            $queryBuilder->orderBy($column, $direction);
        }

        if(null !== $limit)
        {
            $queryBuilder->limit($limit);
        }
    }

    $compiledQuery = $queryBuilder->compile();

    return [
        $compiledQuery->sql(),
        $compiledQuery->params()
    ];
}

/**
 * @internal
 *
 * Unescape binary data
 *
 * @param QueryExecutor                        $queryExecutor
 * @param array<string, string|int|null|float> $set
 *
 * @return array<string, string|int|null|float>
 */
function unescapeBinary(QueryExecutor $queryExecutor, array $set): array
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
