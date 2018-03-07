<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Compiler\Base;

use Symfony\Component\DependencyInjection;

/**
 * Init service handlers
 */
final class ServicesCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @inheritdoc
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $services = [];

        foreach($container->findTaggedServiceIds('service_bus.service') as $id => $tags)
        {
            $container
                ->getDefinition($id)
                ->setPublic(true);

            $services[] = $id;
        }

        $container
            ->getDefinition('service_bus.message_bus.configurator')
            ->setArgument(2, $services);
    }
}
