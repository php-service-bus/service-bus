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

use Desperado\ServiceBus\Modules\ModuleInterface;
use Symfony\Component\DependencyInjection;

/**
 * Boot modules
 */
final class ModulesCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @inheritdoc
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\MessageBusAlreadyCreatedException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Exception
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $messageBusBuilder = $container->get('service_bus.message_bus.builder');

        foreach($container->findTaggedServiceIds('service_bus.module') as $id => $tags)
        {
            /** @var ModuleInterface $module */
            $module = $container->get($id);

            $module->boot($messageBusBuilder);
        }
    }
}
