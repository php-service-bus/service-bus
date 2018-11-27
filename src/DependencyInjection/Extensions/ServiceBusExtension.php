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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Share extension
 */
final class ServiceBusExtension extends Extension
{
    /**
     * @inheritdoc
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param array<string, mixed> $configs
     * @param ContainerBuilder     $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator());
        $loader->load(__DIR__ . '/../service_bus.yaml');

        /**
         * @var string $key
         * @var mixed $value
         *
         * @psalm-suppress MixedAssignment Cannot assign $value to a mixed type
         */
        foreach($configs as $key => $value)
        {
            $container->setParameter($key, $value);
        }
    }
}
