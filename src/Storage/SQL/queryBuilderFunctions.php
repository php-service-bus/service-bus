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

namespace Desperado\ServiceBus\Storage\SQL;

use Latitude\QueryBuilder\CriteriaInterface;
use Latitude\QueryBuilder\Engine\PostgresEngine;
use function Latitude\QueryBuilder\field;
use Latitude\QueryBuilder\Query\DeleteQuery;
use Latitude\QueryBuilder\Query\InsertQuery;
use Latitude\QueryBuilder\Query\SelectQuery;
use Latitude\QueryBuilder\Query\UpdateQuery;
use Latitude\QueryBuilder\QueryFactory;

/**
 * Create query builder
 *
 * @return QueryFactory
 */
function queryBuilder(): QueryFactory
{
    return new QueryFactory(new PostgresEngine());
}

/**
 * @param string                  $field
 * @param int|string|float|object $value
 *
 * @return \Latitude\QueryBuilder\CriteriaInterface
 *
 * @throws \LogicException
 */
function equalsCriteria(string $field, $value): CriteriaInterface
{
    if(true === \is_object($value))
    {
        $value = castObjectToString($value);
    }

    return field($field)->eq($value);
}

/**
 * @param string                  $field
 * @param int|string|float|object $value
 *
 * @return \Latitude\QueryBuilder\CriteriaInterface
 *
 * @throws \LogicException
 */
function notEqualsCriteria(string $field, $value): CriteriaInterface
{
    if(true === \is_object($value))
    {
        $value = castObjectToString($value);
    }

    return field($field)->notEq($value);
}

/**
 * Create select query
 *
 * @param string $fromTable
 * @param string ...$columns
 *
 * @return SelectQuery
 */
function selectQuery(string $fromTable, string ...$columns): SelectQuery
{
    return queryBuilder()->select(...$columns)->from($fromTable);
}

/**
 * Создание query builder'a для update запросов
 *
 * @param string $tableName
 * @param array  $values
 *
 * @return UpdateQuery
 */
function updateQuery(string $tableName, array $values): UpdateQuery
{
    return queryBuilder()->update($tableName, $values);
}

/**
 * Create delete query
 *
 * @param string $fromTable
 *
 * @return DeleteQuery
 */
function deleteQuery(string $fromTable): DeleteQuery
{
    return queryBuilder()->delete($fromTable);
}

/**
 * Create insert query
 *
 * @param string $toTable
 * @param array<string, mixed>|object $toInsert
 *
 * @return InsertQuery
 */
function insertQuery(string $toTable, $toInsert): InsertQuery
{
    if(true === \is_object($toInsert))
    {
        /** @var object $toInsert */

        $rows = castObjectToArray($toInsert);
    }
    else
    {
        /** @var array $rows */
        $rows = $toInsert;
    }

    return queryBuilder()->insert($toTable, $rows);
}

/**
 * Receive object as array (property/value)
 *
 * @param object $object
 *
 * @return array<mixed, mixed>
 *
 * @throws \LogicException
 */
function castObjectToArray(object $object): array
{
    $result = [];

    foreach(getObjectVars($object) as $key => $value)
    {
        $result[toSnakeCase($key)] = cast($key, $value);
    }

    return $result;
}

/**
 * Gets the properties of the given object
 *
 * @param object $object
 *
 * @return array<mixed, mixed>
 */
function getObjectVars(object $object): array
{
    $closure = \Closure::bind(
        function(): array
        {
            /** @psalm-suppress InvalidScope */
            return \get_object_vars($this);
        },
        $object,
        $object
    );

    return $closure();
}

/**
 * Convert string from lowerCamelCase to snake_case
 *
 * @param string $string
 *
 * @return string
 */
function toSnakeCase(string $string): string
{
    return \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
}

/**
 * @param string $key
 * @param mixed  $value
 *
 * @psalm-return scalar|null
 * @return int|float|null|string
 *
 * @throws \LogicException
 */
function cast(string $key, $value)
{
    if(null === $value || true === \is_scalar($value))
    {
        return $value;
    }

    if(true === \is_object($value))
    {
        return castObjectToString($value);
    }

    throw new \LogicException(
        \sprintf(
            'The "%s" property must contain a scalar value. "%s" given',
            $key,
            \gettype($value)
        )
    );
}

/**
 * Cast object to string
 *
 * @param object $object
 *
 * @return string
 *
 * @throws \LogicException
 */
function castObjectToString(object $object): string
{
    if(true === \method_exists($object, '__toString'))
    {
        /** @psalm-suppress InvalidCast */
        return (string) $object;
    }

    throw new \LogicException(
        \sprintf('"%s" must implements "__toString" method', \get_class($object))
    );
}
