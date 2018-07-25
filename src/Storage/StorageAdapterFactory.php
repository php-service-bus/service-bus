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
    public const ADAPTER_DOCTRINE       = 'doctrine';
    public const ADAPTER_ASYNC_POSTGRES = 'asyncPostgres';

    private const SUPPORTED = [
        self::ADAPTER_DOCTRINE       => DoctrineDBALAdapter::class,
        self::ADAPTER_ASYNC_POSTGRES => AmpPostgreSQLAdapter::class
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
        if(true === isset(self::SUPPORTED[$adapter]))
        {
            /** @var string $adapterClass */
            $adapterClass = self::SUPPORTED[$adapter];

            if(true === \class_exists($adapterClass) && true === \is_a($adapterClass, StorageAdapter::class, true))
            {
                return new $adapterClass(StorageConfiguration::fromDSN($connectionDSN));
            }
        }

        throw new \LogicException(
            \sprintf('Invalid adapter specified ("%s")', $adapter)
        );
    }
}
