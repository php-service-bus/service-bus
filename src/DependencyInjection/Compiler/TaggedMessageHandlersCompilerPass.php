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
 * Collect message handlers
 */
final class TaggedMessageHandlersCompilerPass implements CompilerPassInterface
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

        /** @var array<string, array<mixed, string>> $taggedServices */
        $taggedServices = $container->findTaggedServiceIds('service_bus.service');

        foreach($taggedServices as $id => $tags)
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
     * @throws \LogicException
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

                if(true === self::supportedType($parameter))
                {
                    if(false === $container->has($reflectionTypeName))
                    {
                        throw new \LogicException(
                            \sprintf('The "%s" service was not found in the dependency container', $reflectionTypeName)
                        );
                    }

                    $servicesReference[$reflectionTypeName] = new ServiceClosureArgument(new Reference($reflectionTypeName));
                }
            }
        }
    }

    /**
     * @param \ReflectionParameter $parameter
     *
     * @return bool
     */
    private static function supportedType(\ReflectionParameter $parameter): bool
    {
        /** @var \ReflectionType $reflectionType */
        $reflectionType     = $parameter->getType();
        $reflectionTypeName = $reflectionType->getName();

        return (true === \class_exists($reflectionTypeName) || true === \interface_exists($reflectionTypeName)) &&
            false === \is_a($reflectionTypeName, Message::class, true) &&
            false === \is_a($reflectionTypeName, MessageDeliveryContext::class, true);
    }
}
