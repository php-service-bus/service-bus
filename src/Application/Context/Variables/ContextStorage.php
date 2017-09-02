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

namespace Desperado\ConcurrencyFramework\Application\Context\Variables;

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Application\Context\Exceptions;
use Desperado\ConcurrencyFramework\Application\Storage\StorageManagerRegistry;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\AbstractStorageManager;

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
     * Get storage for specified entry
     *
     * @param string $entry
     *
     * @return AbstractStorageManager
     *
     * @throws Exceptions\StorageManagerWasNotConfiguredException
     */
    public function getStorage(string $entry): AbstractStorageManager
    {
        if(true === $this->storageManagerRegistry->has($entry))
        {
            return $this->storageManagerRegistry->get($entry);
        }

        throw new Exceptions\StorageManagerWasNotConfiguredException(
            \sprintf('The manager for the "%s" was not configured in "parameters.yaml" file', $entry)
        );
    }
}
