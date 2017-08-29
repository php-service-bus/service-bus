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
use Desperado\ConcurrencyFramework\Domain\EventSourced\AggregateRootInterface;
use Desperado\ConcurrencyFramework\Domain\EventSourced\SagaInterface;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\AbstractStorageManager;

/**
 * Context storages
 */
class ContextStorage
{
    /**
     * Storage managers
     *
     * @var AbstractStorageManager[]
     */
    private $storageManagers;

    /**
     * @param AbstractStorageManager[] $storageManagers
     */
    public function __construct(array $storageManagers)
    {
        $this->storageManagers = $storageManagers;
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
        foreach($this->storageManagers as $manager)
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
        if(true === \array_key_exists($entry, $this->storageManagers))
        {
            return $this->storageManagers[$entry];
        }

        if(\is_a($entry, SagaInterface::class, true))
        {
            $type = 'saga';
        }
        else if(\is_a($entry, AggregateRootInterface::class, true))
        {
            $type = 'aggregate';
        }
        else
        {
            $type = 'dbal';
        }

        throw new Exceptions\StorageManagerWasNotConfiguredException(
            \sprintf(
                'The manager for the "%s" (type "%s") was not configured in "parameters.yaml" file',
                $entry, $type
            )
        );
    }
}
