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

namespace Desperado\Framework\Infrastructure\EventSourcing\Storage;

use Desperado\Framework\Infrastructure\EventSourcing\Storage\Configuration\StorageConfigurationConfig;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\Configuration\UnSupportedStorageDriverException;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\Doctrine\SyncDoctrineStorage;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\InMemory\InMemoryEventStorage;

/**
 * Storage factory
 */
class StorageFactory
{
    public const IN_MEMORY_STORAGE = 'inMemory';
    public const DOCTRINE_POSTGRES_STORAGE = 'doctrinePgSql';

    private const SUPPORTED_DRIVERS = [
        self::IN_MEMORY_STORAGE         => InMemoryEventStorage::class,
        self::DOCTRINE_POSTGRES_STORAGE => SyncDoctrineStorage::class
    ];

    /**
     * Create storage adapter
     *
     * Example:
     *
     *  - doctrine:localhost:5432?user=postgres&password=123456789&dbname=temp&encoding=UTF-8
     *  - inMemory:?
     *
     * @param string $storageConnectionDSN
     *
     * @return EventStorageInterface
     *
     * @throws UnSupportedStorageDriverException
     */
    public static function create(string $storageConnectionDSN): EventStorageInterface
    {
        $config = StorageConfigurationConfig::fromDSN($storageConnectionDSN);
        $driversList = \array_keys(self::SUPPORTED_DRIVERS);

        if(true === \in_array($config->getDriver(), $driversList, true))
        {
            $storageDriverClass = self::SUPPORTED_DRIVERS[$config->getDriver()];

            switch($config->getDriver())
            {
                case self::IN_MEMORY_STORAGE:
                    $storage = new $storageDriverClass();
                    break;

                case self::DOCTRINE_POSTGRES_STORAGE:
                    $storage = new $storageDriverClass($config);
                    break;

                default:
                    throw new UnSupportedStorageDriverException(
                        \sprintf(
                            'Cant initialize event storage for driver "%s"', $config->getDriver()
                        )
                    );
            }

            return $storage;
        }

        throw new UnSupportedStorageDriverException(
            \sprintf(
                'Specified driver ("%s") not supported. Expected choices: %s',
                $config->getDriver(), \implode(', ', $driversList)
            )
        );
    }
}
