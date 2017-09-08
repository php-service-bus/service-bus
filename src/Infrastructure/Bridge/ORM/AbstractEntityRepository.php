<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\Bridge\ORM;

use Doctrine\ORM\EntityRepository;

/**
 * Base repository class
 */
abstract class AbstractEntityRepository extends EntityRepository
{
    /**
     * Execute plain sql query
     *
     * @param string   $query
     * @param array    $parameters
     * @param callable $onComplete
     * @param callable $onFailed
     *
     * @return void
     */
    public function executeNativeQuery(
        string $query,
        array $parameters = [],
        callable $onComplete,
        callable $onFailed
    ): void
    {
        try
        {
            $connection = $this->_em->getConnection();
            $statement = $connection->prepare($query);

            $statement->execute($parameters);

            $onComplete();
        }
        catch(\Throwable $throwable)
        {
            $onFailed($throwable);
        }
    }

    /**
     * Execute insert query
     *
     * @param string   $query
     * @param array    $parameters
     * @param callable $onComplete
     * @param callable $onFailed
     *
     * @return void
     */
    public function executeInsert(
        string $query,
        array $parameters,
        callable $onComplete,
        callable $onFailed
    ): void
    {
        $this->executeNativeQuery(
            $query,
            $parameters,
            function() use ($onComplete)
            {
                return $onComplete((string) $this->_em->getConnection()->lastInsertId());
            },
            $onFailed
        );
    }
}