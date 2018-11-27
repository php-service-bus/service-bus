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

namespace Desperado\ServiceBus\Infrastructure\Storage;

use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;

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
     * Creating inMemory adapter (only for testing)
     *
     * @return DoctrineDBALAdapter
     */
    public static function inMemory(): DoctrineDBALAdapter
    {
        return new DoctrineDBALAdapter(
            StorageConfiguration::fromDSN('sqlite:///:memory:')
        );
    }

    /**
     * @param string $adapter @see StorageAdapterFactory::SUPPORTED
     * @param string $connectionDSN DSN examples:
     *                              - inMemory: sqlite:///:memory:
     *                              - AsyncPostgreSQL: pgsql://user:password@host:port/database
     *
     * @return StorageAdapter
     *
     * @throws \LogicException Unsupported adapter specified
     */
    public static function create(string $adapter, string $connectionDSN): StorageAdapter
    {
        if(
            true === \in_array($adapter, self::SUPPORTED, true) &&
            true === \class_exists($adapter) &&
            true === \is_a($adapter, StorageAdapter::class, true)
        )
        {
            /** @var StorageAdapter $adapter */
            $adapter = new $adapter(StorageConfiguration::fromDSN($connectionDSN));

            return $adapter;
        }

        throw new \LogicException(
            \sprintf('Invalid adapter specified ("%s")', $adapter)
        );
    }
}
