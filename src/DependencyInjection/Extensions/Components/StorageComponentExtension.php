<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Extensions\Components;

use Desperado\StorageComponent\SQL\AsyncPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\StorageComponent\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\StorageComponent\SQL\SqlDatabaseAdapter;
use Desperado\StorageComponent\StorageConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class StorageComponentExtension extends Extension
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
            $driver = $configs['storage']['driver'] ?? 'dbal';
            $dsn    = $configs['storage']['dsn'] ?? '';

            /** Configuration */

            $storageConfigDefinition = new Definition(StorageConfiguration::class);
            $storageConfigDefinition->setFactory(
                \sprintf(
                    '%s::%s',
                    StorageConfiguration::class,
                    'dbal' === $driver ? 'syncDBAL' : 'asyncPostgres'
                )
            );

            $storageConfigDefinition->setArgument('$connectionDSN', $dsn);

            $container->setDefinition(StorageConfiguration::class, $storageConfigDefinition);

            /** Storage backend */

            $storageAdapter = new Definition(
                SqlDatabaseAdapter::class,
                [new Reference(StorageConfiguration::class)]
            );

            $storageAdapter->setClass(
                'dbal' === $driver
                    ? DoctrineDBALAdapter::class
                    : AmpPostgreSQLAdapter::class);

            $container->setDefinition(SqlDatabaseAdapter::class, $storageAdapter);

            return;
        }

        throw new \LogicException('Could not find the connection settings for the storage (storage.dsn)');
    }
}
