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

namespace Desperado\Framework\Application\Context\Variables;

use Desperado\Framework\Application\Context\KernelContext;
use Desperado\Framework\Application\Context\Exceptions;
use Desperado\Framework\Application\Storage\StorageManagerRegistry;
use Desperado\Framework\Infrastructure\Bridge\ORM\AbstractEntityRepository;
use Desperado\Framework\Infrastructure\EventSourcing\Aggregate\AbstractAggregateRoot;
use Desperado\Framework\Infrastructure\EventSourcing\Saga\AbstractSaga;
use Desperado\Framework\Infrastructure\StorageManager\AggregateStorageManagerInterface;
use Desperado\Framework\Infrastructure\StorageManager\EntityManagerInterface;
use Desperado\Framework\Infrastructure\StorageManager\SagaStorageManagerInterface;

/**
 * Context storages
 */
class ContextStorage
{
    /**
     * Storage managers
     *
     * @var StorageManagerRegistry
     */
    private $storageManagerRegistry;

    /**
     * @param StorageManagerRegistry $storageManagerRegistry
     */
    public function __construct(StorageManagerRegistry $storageManagerRegistry)
    {
        $this->storageManagerRegistry = $storageManagerRegistry;
    }

    /**
     * Flush changes
     *
     * @param KernelContext $context
     *
     * @return void
     */
    public function flush(KernelContext $context): void
    {
        foreach($this->storageManagerRegistry as $manager)
        {
            $manager->commit($context);
        }
    }

    /**
     * Get saga storage manager
     *
     * @param string $sagaNamespace
     *
     * @return SagaStorageManagerInterface
     *
     * @throws Exceptions\StorageManagerWasNotConfiguredException
     */
    public function getSagaStorageManager(string $sagaNamespace): SagaStorageManagerInterface
    {
        if(true === $this->storageManagerRegistry->has($sagaNamespace))
        {
            /** @var SagaStorageManagerInterface $sagaManager */
            $sagaManager = $this->storageManagerRegistry->get($sagaNamespace);

            return $sagaManager;
        }

        throw new Exceptions\StorageManagerWasNotConfiguredException(
            \sprintf('The manager for the saga "%s" was not configured in "parameters.yaml" file', $sagaNamespace)
        );
    }

    /**
     * Get entity manager
     *
     * @param string $entityNamespace
     *
     * @return AbstractEntityRepository
     *
     * @throws Exceptions\StorageManagerWasNotConfiguredException
     */
    public function getEntityRepository(string $entityNamespace): AbstractEntityRepository
    {
        return $this->getEntityManager($entityNamespace)->getRepository($entityNamespace);
    }

    /**
     * Get entity manager
     *
     * @param string $entityNamespace
     *
     * @return EntityManagerInterface
     *
     * @throws Exceptions\StorageManagerWasNotConfiguredException
     */
    public function getEntityManager(string $entityNamespace): EntityManagerInterface
    {
        if(true === $this->storageManagerRegistry->has($entityNamespace))
        {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->storageManagerRegistry->get($entityNamespace);

            return $entityManager;
        }

        throw new Exceptions\StorageManagerWasNotConfiguredException(
            \sprintf('The manager for the entity "%s" was not configured in "parameters.yaml" file', $entityNamespace)
        );
    }

    /**
     * Persist saga
     *
     * @param AbstractSaga $saga
     *
     * @return void
     */
    public function persistSaga(AbstractSaga $saga): void
    {
        $this->getSagaStorageManager(\get_class($saga))->persist($saga);
    }

    /**
     * Get aggregate storage manager
     *
     * @param string $aggregateNamespace
     *
     * @return AggregateStorageManagerInterface
     *
     * @throws Exceptions\StorageManagerWasNotConfiguredException
     */
    public function getAggregateStorageManager(string $aggregateNamespace): AggregateStorageManagerInterface
    {
        if(true === $this->storageManagerRegistry->has($aggregateNamespace))
        {
            /** @var AggregateStorageManagerInterface $aggregateManager */
            $aggregateManager = $this->storageManagerRegistry->get($aggregateNamespace);

            return $aggregateManager;
        }

        throw new Exceptions\StorageManagerWasNotConfiguredException(
            \sprintf('The manager for the aggregate "%s" was not configured in "parameters.yaml" file', $aggregateNamespace)
        );
    }

    /**
     * Persist aggregate
     *
     * @param AbstractAggregateRoot $aggregateRoot
     *
     * @return void
     */
    public function persistAggregate(AbstractAggregateRoot $aggregateRoot): void
    {
        $this->getAggregateStorageManager(\get_class($aggregateRoot))->persist($aggregateRoot);
    }
}
