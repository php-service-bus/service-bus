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

namespace Desperado\Framework\Infrastructure\StorageManager;

use Desperado\Framework\Infrastructure\Bridge\ORM\AbstractEntityRepository;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;

/**
 * ORM storage manager (Doctrine2)
 */
class EntityManager implements EntityManagerInterface
{
    /**
     * Entity manager
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param Connection    $connection
     * @param Configuration $doctrineConfiguration
     */
    public function __construct(Connection $connection, Configuration $doctrineConfiguration)
    {
        $this->entityManager = DoctrineEntityManager::create($connection, $doctrineConfiguration);
    }

    /**
     * @inheritdoc
     */
    public function commit(DeliveryContextInterface $context, callable $onComplete = null, callable $onFailed = null): void
    {
        try
        {
            $this->entityManager->flush();

            if(null !== $onComplete)
            {
                $onComplete();
            }
        }
        catch(\Throwable $throwable)
        {
            if(null !== $onFailed)
            {
                $onFailed($throwable);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function persist($entityObject): void
    {
        $this->entityManager->persist($entityObject);
    }

    /**
     * @inheritdoc
     */
    public function flush($entityObject, callable $onSuccess, callable $onFailed): void
    {
        try
        {
            $this->entityManager->flush($entityObject);


            $onSuccess();
        }
        catch(\Throwable $throwable)
        {
            $onFailed($throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function getRepository(string $entityNamespace): AbstractEntityRepository
    {
        return $this->entityManager->getRepository($entityNamespace);
    }
}
