<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Application\DependencyInjection\Extensions;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Share extension.
 */
final class ServiceBusExtension extends Extension
{
    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-param    array<string, mixed> $configs
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator());
        $loader->load(__DIR__ . '/../service_bus.yaml');

        /**
         * @var string $key
         * @var mixed  $value
         */
        foreach ($configs as $key => $value)
        {
            $container->setParameter($key, $value);
        }
    }
}
