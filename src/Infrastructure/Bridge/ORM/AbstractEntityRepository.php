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
     * Executes a function in a transaction
     *
     * @param callable $transaction
     * @param callable $onComplete function() {}
     * @param callable $onFailed   function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function transactional(callable $transaction, callable $onComplete, callable $onFailed)
    {
        try
        {
            $this
                ->getEntityManager()
                ->transactional($transaction);

            $onComplete();
        }
        catch(\Throwable $throwable)
        {
            $onFailed($throwable);
        }
    }

    /**
     * Execute plain sql query
     *
     * @param string   $query
     * @param array    $parameters
     * @param callable $onComplete function() {}
     * @param callable $onFailed   function(\Throwable $throwable) {}
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
     * Finds an object by its primary key / identifier
     *
     * @param string|int $id
     * @param callable   $onComplete function($result = null) {}
     * @param callable   $onFailed   function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function awaitFind($id, callable $onComplete, callable $onFailed): void
    {
        try
        {
            $result = $this->getEntityManager()->find($this->getEntityName(), $id);

            $onComplete($result);
        }
        catch(\Throwable $throwable)
        {
            $onFailed($throwable);
        }
    }

    /**
     * Finds a single object by a set of criteria
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param callable   $onComplete function($result = null) {}
     * @param callable   $onFailed   function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function awaitFindOneBy(array $criteria, ?array $orderBy, callable $onComplete, callable $onFailed): void
    {
        try
        {
            $persister = $this
                ->getEntityManager()
                ->getUnitOfWork()
                ->getEntityPersister(
                    $this->getEntityName()
                );

            $result = $persister->load($criteria, null, null, [], null, 1, $orderBy);

            $onComplete($result);
        }
        catch(\Throwable $throwable)
        {
            $onFailed($throwable);
        }
    }

    /**
     * Finds all entities in the repository
     *
     * @param callable $onComplete function(array $result) {}
     * @param callable $onFailed   function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function awaitFindAll(callable $onComplete, callable $onFailed): void
    {
        $this->awaitFindBy([], null, null, null, $onComplete, $onFailed);
    }

    /**
     * Finds entities by a set of criteria
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     * @param callable   $onComplete function(array $result) {}
     * @param callable   $onFailed   function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function awaitFindBy(
        array $criteria,
        ?array $orderBy,
        ?int $limit,
        ?int $offset,
        callable $onComplete,
        callable $onFailed
    ): void
    {
        try
        {
            $persister = $this->getEntityManager()
                ->getUnitOfWork()
                ->getEntityPersister($this->getEntityName());

            $result = $persister->loadAll($criteria, $orderBy, $limit, $offset);

            $onComplete($result);
        }
        catch(\Throwable $throwable)
        {
            $onFailed($throwable);
        }
    }

    /**
     * @inheritdoc
     * @deprecated Use awaitFind method
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        throw new \BadMethodCallException('Use awaitFind method');
    }

    /**
     * @inheritdoc
     * @deprecated Use awaitFindAll method
     */
    public function findAll()
    {
        throw new \BadMethodCallException('Use awaitFindAll method');
    }

    /**
     * @inheritdoc
     * @deprecated Use awaitFindBy method
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        throw new \BadMethodCallException('Use awaitFindBy method');
    }

    /**
     * @inheritdoc
     * @deprecated Use awaitFindOneBy method
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        throw new \BadMethodCallException('Use awaitFindOneBy method');
    }
}
