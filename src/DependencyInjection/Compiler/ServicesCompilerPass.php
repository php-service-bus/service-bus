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

namespace Desperado\ServiceBus\DependencyInjection\Compiler;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Collect services
 */
final class ServicesCompilerPass implements CompilerPassInterface
{

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        $servicesReference = [];
        $serviceIds        = [];

        foreach($container->findTaggedServiceIds('service_bus.service') as $id => $tags)
        {
            $serviceClass = $container->getDefinition($id)->getClass();

            if(null !== $serviceClass)
            {
                $this->collectServiceDependencies($serviceClass, $container, $servicesReference);

                $serviceIds[] = $serviceClass;

                $servicesReference[\sprintf('%s_service', $serviceClass)] = new ServiceClosureArgument(
                    new Reference($id)
                );
            }
        }

        $container->setParameter('service_bus.services_map', $serviceIds);

        $container
            ->register('service_bus.services_locator', ServiceLocator::class)
            ->setPublic(true)
            ->setArguments([$servicesReference]);
    }

    /**
     * @param string           $serviceClass
     * @param ContainerBuilder $container
     * @param array            $servicesReference (passed by reference)
     *
     * @return void
     * @throws \ReflectionException
     */
    private function collectServiceDependencies(string $serviceClass, ContainerBuilder $container, array &$servicesReference): void
    {
        $reflectionClass = new \ReflectionClass($serviceClass);

        foreach($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod)
        {
            foreach($reflectionMethod->getParameters() as $parameter)
            {
                if(false === $parameter->hasType())
                {
                    continue;
                }

                /** @var \ReflectionType $reflectionType */
                $reflectionType     = $parameter->getType();
                $reflectionTypeName = $reflectionType->getName();

                if(true === self::supportedType($parameter, $container))
                {
                    $servicesReference[$reflectionTypeName] = new ServiceClosureArgument(new Reference($reflectionTypeName));
                }
            }
        }
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param ContainerBuilder     $container
     *
     * @return bool
     */
    private static function supportedType(\ReflectionParameter $parameter, ContainerBuilder $container): bool
    {
        /** @var \ReflectionType $reflectionType */
        $reflectionType     = $parameter->getType();
        $reflectionTypeName = $reflectionType->getName();

        return true === \class_exists($reflectionTypeName) &&
            false === \is_a($reflectionTypeName, Message::class, true) &&
            false === \is_a($reflectionTypeName, MessageDeliveryContext::class, true) &&
            true === $container->has($reflectionTypeName);
    }
}
