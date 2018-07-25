<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Storage;

use Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;

/**
 *
 */
final class StorageAdapterFactory
{
    private const SUPPORTED = [
        DoctrineDBALAdapter::class,
        AmpPostgreSQLAdapter::class
    ];

    /**
     * @return DoctrineDBALAdapter
     */
    public static function inMemory(): DoctrineDBALAdapter
    {
        return new DoctrineDBALAdapter(
            StorageConfiguration::fromDSN('sqlite:///:memory:')
        );
    }

    /**
     * @param string $adapter
     * @param string $connectionDSN
     *
     * @return StorageAdapter
     *
     * @throws \LogicException
     */
    public static function create(string $adapter, string $connectionDSN): StorageAdapter
    {
        if(
            true === \in_array($adapter, self::SUPPORTED, true) &&
            true === \class_exists($adapter) &&
            true === \is_a($adapter, StorageAdapter::class, true)
        )
        {
            return new $adapter(StorageConfiguration::fromDSN($connectionDSN));
        }

        throw new \LogicException(
            \sprintf('Invalid adapter specified ("%s")', $adapter)
        );
    }
}
