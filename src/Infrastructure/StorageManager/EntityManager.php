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
     * Entity namespace
     *
     * @var string
     */
    private $entityNamespace;

    /**
     * Connections pool
     *
     * @var Connection
     */
    private $connection;

    /**
     * Entity manager
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param string        $entityNamespace
     * @param Connection    $connection
     *
     * @throws \LogicException
     */
    public function __construct(string $entityNamespace, Connection $connection)
    {
        if('' === $entityNamespace)
        {
            throw new \LogicException('Entity namespace can\'t be empty');
        }

        $this->entityNamespace = $entityNamespace;
        $this->connection = $connection;

        $doctrineConfiguration = new Configuration();
        $doctrineConfiguration->setMetadataDriverImpl($doctrineConfiguration->newDefaultAnnotationDriver());

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

    public function getRepository()
    {
        $this->entityManager->getRepository($this->entityNamespace);
    }

}
