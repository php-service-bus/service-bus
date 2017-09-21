<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\StorageManager;

use Desperado\EventSourcing\AggregateStorageManagerInterface;
use Desperado\EventSourcing\Saga\SagaStorageManagerInterface;

/**
 * Storage managers registry
 */
class StorageManagerRegistry
{
    /**
     * Sagas storage managers
     *
     * @var SagaStorageManagerInterface[]
     */
    private $sagaManagers = [];

    /**
     * Aggregates storage managers
     *
     * @var AggregateStorageManagerInterface[]
     */
    private $aggregateManagers = [];

    /**
     * Add storage manager for specified aggregate
     *
     * @param string                           $aggregateNamespace
     * @param AggregateStorageManagerInterface $storageManager
     *
     * @return StorageManagerRegistry
     */
    public function addAggregateStorageManager(
        string $aggregateNamespace,
        AggregateStorageManagerInterface $storageManager
    ): self
    {
        $this->aggregateManagers[$aggregateNamespace] = $storageManager;

        return $this;
    }

    /**
     * Add storage manager for specified saga
     *
     * @param string                      $sagaNamespace
     * @param SagaStorageManagerInterface $storageManager
     *
     * @return StorageManagerRegistry
     */
    public function addSagaStorageManager(
        string $sagaNamespace,
        SagaStorageManagerInterface $storageManager
    ): self
    {
        $this->sagaManagers[$sagaNamespace] = $storageManager;

        return $this;
    }

    /**
     * Get saga storage managers
     *
     * @return SagaStorageManagerInterface[]
     */
    public function getSagaManagers(): array
    {
        return $this->sagaManagers;
    }

    /**
     * Get aggregate storage managers
     *
     * @return AggregateStorageManagerInterface[]
     */
    public function getAggregateManagers(): array
    {
        return $this->aggregateManagers;
    }

    /**
     * Has manager for specified aggregate
     *
     * @param string $aggregateNamespace
     *
     * @return bool
     */
    public function hasAggregateManager(string $aggregateNamespace): bool
    {
        return isset($this->aggregateManagers[$aggregateNamespace]);
    }

    /**
     * Get manager or specified aggregate
     *
     * @param string $aggregateNamespace
     *
     * @return AggregateStorageManagerInterface|null
     */
    public function getAggregateManager(string $aggregateNamespace): ?AggregateStorageManagerInterface
    {
        return true === $this->hasAggregateManager($aggregateNamespace)
            ? $this->aggregateManagers[$aggregateNamespace]
            : null;
    }

    /**
     * Get manager or specified saga
     *
     * @param string $sagaNamespace
     *
     * @return SagaStorageManagerInterface|null
     */
    public function getSagaManager(string $sagaNamespace): ?SagaStorageManagerInterface
    {
        return true === $this->hasAggregateManager($sagaNamespace)
            ? $this->sagaManagers[$sagaNamespace]
            : null;
    }

    /**
     * Has manager for specified saga
     *
     * @param string $sagaNamespace
     *
     * @return bool
     */
    public function hasSagaManager(string $sagaNamespace): bool
    {
        return isset($this->sagaManagers[$sagaNamespace]);
    }
}
