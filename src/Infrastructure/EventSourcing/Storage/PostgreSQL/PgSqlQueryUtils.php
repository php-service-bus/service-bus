<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\PostgreSQL;

/**
 * PgSQL query helper
 */
class PgSqlQueryUtils
{
    /**
     * Generate sql multi query
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $values
     *
     * @return array
     */
    public static function createMultiInsertQuery(string $tableName, array $columns, array $values)
    {
        $lastIndex = 0;
        $sql = \sprintf('INSERT INTO %s (%s) VALUES ', $tableName, \implode(', ', \array_values($columns)));

        $variables = [];

        foreach($values as $row)
        {
            $variables[$lastIndex + 1] = $row[0];
            $variables[$lastIndex + 2] = $row[1];
            $variables[$lastIndex + 3] = $row[2];
            $variables[$lastIndex + 4] = $row[3];
            $variables[$lastIndex + 5] = $row[4];
            $variables[$lastIndex + 6] = $row[5];

            $sql .= '(' . implode(
                    ', ', array_map(
                        function($each)
                        {
                            return \sprintf('$%d', $each);
                        },
                        \array_keys($variables)
                    )
                )
                . '),';

            $lastIndex = $lastIndex + \count($columns);
        }

        return [
            'query' => \rtrim($sql, ','),
            'parameters' => $variables
        ];
    }


    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
