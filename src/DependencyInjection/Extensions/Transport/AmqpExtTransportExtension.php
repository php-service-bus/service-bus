<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Extensions\Transport;

use Desperado\ServiceBus\Transport\AmqpExt\AmqpConfiguration;
use Desperado\ServiceBus\Transport\AmqpExt\Amqp;
use Desperado\ServiceBus\Transport\Transport;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class AmqpExtTransportExtension extends Extension
{
    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        if(true === isset($configs['amqpExt']))
        {
            self::createConfigurationDefinition($configs['amqpExt']['dsn'] ?? '', $container);
            self::configureTransportDefinition($container);

            return;
        }

        throw new \LogicException('Could not find the connection settings for the broker (amqpExt.dsn)');
    }

    /**
     * @param ContainerBuilder $container
     * @param array<mixed, \Desperado\ServiceBus\Transport\AmqpExt\AmqpTopic> $topicCollection
     * @param array<mixed, \Desperado\ServiceBus\Transport\AmqpExt\AmqpQueue> $queueCollection
     *
     * @return void
     *
     * @throws \LogicException
     */
    private static function configureTransportDefinition(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(Transport::class);
        $definition->setClass(Amqp::class);

        $definition->setArgument('$amqpConfiguration', new Reference(AmqpConfiguration::class));
    }

    /**
     * @param string           $connectionDsn
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @throws \LogicException
     */
    private static function createConfigurationDefinition(string $connectionDsn, ContainerBuilder $container): void
    {
        if('' === $connectionDsn)
        {
            throw new \LogicException('amqpExt[dsn] must be specified and contains the correct connection DSN');
        }

        $configDefinition = new Definition(AmqpConfiguration::class);
        $configDefinition->setFactory(\sprintf('%s::create', AmqpConfiguration::class));
        $configDefinition->setArgument('$connectionDSN', $connectionDsn);

        $container->setDefinition(AmqpConfiguration::class, $configDefinition);
    }
}
