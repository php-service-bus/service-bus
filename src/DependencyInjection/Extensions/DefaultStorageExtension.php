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

namespace Desperado\ServiceBus\DependencyInjection\Extensions;

use Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class DefaultStorageExtension extends Extension
{
    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        if(true === isset($configs['storage']))
        {
            $driver = $configs['storage']['adapter'] ?? DoctrineDBALAdapter::class;
            $dsn    = $configs['storage']['dsn'] ?? '';

            /** Configuration */

            $storageConfigDefinition = new Definition(StorageConfiguration::class);
            $storageConfigDefinition->setFactory('fromDSN');
            $storageConfigDefinition->setArgument('$connectionDSN', $dsn);

            $container->setDefinition(StorageConfiguration::class, $storageConfigDefinition);

            /** Storage backend */

            $storageAdapter = new Definition(
                StorageAdapter::class,
                [new Reference(StorageConfiguration::class)]
            );

            $storageAdapter->setClass(
                DoctrineDBALAdapter::class === $driver
                    ? DoctrineDBALAdapter::class
                    : AmpPostgreSQLAdapter::class
            );

            $container->setDefinition(StorageAdapter::class, $storageAdapter);

            return;
        }

        throw new \LogicException('Could not find the connection settings for the storage (storage.dsn)');
    }
}
