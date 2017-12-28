<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\DependencyInjection\Compiler;

use Desperado\Framework\Modules\ModuleInterface;
use Symfony\Component\DependencyInjection;

/**
 * Boot modules
 */
class ModulesCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(DependencyInjection\ContainerBuilder $container)
    {
        /** @var \Desperado\CQRS\MessageBus\MessageBusBuilder $messageBusBuilder */
        $messageBusBuilder = $container->get('kernel.cqrs.message_bus_builder');

        foreach($container->findTaggedServiceIds('kernel.module') as $id => $tags)
        {
            /** @var ModuleInterface $module */
            $module = $container->get($id);

            $module->boot($messageBusBuilder);
        }
    }
}
