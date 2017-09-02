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

namespace Desperado\Framework\Application\Storage;

use Desperado\Framework\Infrastructure\StorageManager\AbstractStorageManager;
use Desperado\Framework\Infrastructure\StorageManager\SagaStorageManager;

/**
 * Storage managers registry
 */
class StorageManagerRegistry implements \IteratorAggregate
{
    /**
     * Storage managers
     *
     * @var AbstractStorageManager[]
     */
    private $collection = [];

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->collection;
    }

    /**
     * Get manager for specified entry
     *
     * @param string $entryNamespace
     *
     * @return AbstractStorageManager
     */
    public function get(string $entryNamespace): ?AbstractStorageManager
    {
        return true === $this->has($entryNamespace) ? $this->collection[$entryNamespace] : null;
    }

    /**
     * Has manager for specified entry
     *
     * @param string $entryNamespace
     *
     * @return bool
     */
    public function has(string $entryNamespace): bool
    {
        return isset($this->collection[$entryNamespace]);
    }

    /**
     * Add storage manager for specified entry
     *
     * @param string                 $entryNamespace
     * @param AbstractStorageManager $storageManager
     *
     * @return void
     */
    public function add(string $entryNamespace, AbstractStorageManager $storageManager): void
    {
        $this->collection[$entryNamespace] = $storageManager;
    }

    /**
     * Get sagas store manager
     *
     * @return SagaStorageManager[]
     */
    public function getSagaStorageManagers(): array
    {
        return \array_filter(
            \array_map(
                function(AbstractStorageManager $storageManager)
                {
                    return $storageManager instanceof SagaStorageManager ? $storageManager : null;
                },
                $this->collection
            )
        );
    }
}
