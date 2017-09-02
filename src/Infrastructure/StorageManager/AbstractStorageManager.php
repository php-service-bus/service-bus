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

namespace Desperado\ConcurrencyFramework\Infrastructure\StorageManager;

use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\Exceptions\PersistFailException;

/**
 * Base storage manager
 */
abstract class AbstractStorageManager
{
    /**
     * Entity namespace
     *
     * @var string
     */
    private $entityNamespace;

    /**
     * Persisted entries
     *
     * @var \SplObjectStorage
     */
    private $persistMap;

    /**
     * Entries to remove
     *
     * @var \SplObjectStorage
     */
    private $removeMap;

    /**
     * @param string $entityNamespace
     */
    public function __construct(string $entityNamespace)
    {
        $this->entityNamespace = $entityNamespace;

        $this->flushLocalStorage();
    }

    /**
     * Get event sourced entry namespace
     *
     * @return string
     */
    public function getEntityNamespace(): string
    {
        return $this->entityNamespace;
    }

    /**
     * Load event entry
     *
     * @param IdentityInterface $identity
     *
     * @return object|null
     */
    abstract public function load(IdentityInterface $identity);

    /**
     * Add entry to delete list
     *
     * @param object $entry
     *
     * @return void
     *
     * @throws PersistFailException
     */
    public function remove($entry): void
    {
        if(false === $this->persistMap->contains($entry))
        {
            throw new PersistFailException(
                'Object "%s" with ID "%s" not persisted (use persist() method)',
                \get_class($entry), $entry->getId()->toString()
            );
        }

        if(false === $this->removeMap->contains($entry))
        {
            $this->removeMap->attach($entry);
        }
    }

    /**
     * Persist entry
     *
     * @param object $entry
     *
     * @return void
     *
     * @throws PersistFailException
     */
    public function persist($entry): void
    {
        if(true === $this->removeMap->contains($entry))
        {
            throw new PersistFailException(
                'Object "%s" with ID "%s" already persisted and marked to delete',
                \get_class($entry), $entry->getId()->toString()
            );
        }

        if(false === $this->persistMap->contains($entry))
        {
            $this->persistMap->attach($entry);
        }
    }

    /**
     * Commit changes
     *
     * @param DeliveryContextInterface $context
     *
     * @return void
     */
    abstract public function commit(DeliveryContextInterface $context): void;


    /**
     * Flush persist/remove map
     *
     * @return void
     */
    protected function flushLocalStorage(): void
    {
        $this->persistMap = new \SplObjectStorage();
        $this->removeMap = new \SplObjectStorage();
    }

    /**
     * Get persists object
     *
     * @return \SplObjectStorage
     */
    protected function getPersistMap(): \SplObjectStorage
    {
        return $this->persistMap;
    }

    /**
     * Get objects to remove
     *
     * @return \SplObjectStorage
     */
    protected function getRemoveMap(): \SplObjectStorage
    {
        return $this->removeMap;
    }
}
