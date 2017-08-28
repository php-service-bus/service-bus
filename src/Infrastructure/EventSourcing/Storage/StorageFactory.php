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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage;

use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\Configuration\StorageConfigurationConfig;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\Configuration\UnSupportedStorageDriverException;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\InMemory\InMemoryEventStorage;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\PostgreSQL\AmPhpPostgreSQLStorage;

/**
 * Storage factory
 */
class StorageFactory
{
    public const IN_MEMORY_STORAGE = 'inMemory';
    public const AMPHP_POSTGRE_SQL_STORAGE = 'amphpPgSql';

    private const SUPPORTED_DRIVERS = [
        self::IN_MEMORY_STORAGE => InMemoryEventStorage::class,
        self::AMPHP_POSTGRE_SQL_STORAGE => AmPhpPostgreSQLStorage::class
    ];

    /**
     * Create storage adapter
     *
     * Example:
     *
     *  - amphpPgSql:localhost:5432?user=postgres&password=123456789&dbname=temp&encoding=UTF-8
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
                case 'amphpPgsql':
                    $storage = new $storageDriverClass($config);
                    break;
                case 'inMemory':
                    $storage = new $storageDriverClass();
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
