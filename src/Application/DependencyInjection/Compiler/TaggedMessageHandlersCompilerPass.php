<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Application\DependencyInjection\Compiler;

use ServiceBus\Common\Context\ServiceBusContext;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Collect message handlers.
 */
final class TaggedMessageHandlersCompilerPass implements CompilerPassInterface
{
    /**
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        $servicesReference = $serviceIds = [];

        /**
         * @psalm-var array<string, array<mixed, string>> $taggedServices
         */
        $taggedServices = $container->findTaggedServiceIds('service_bus.service');

        foreach ($taggedServices as $id => $tags)
        {
            /** @psalm-var class-string|null $serviceClass */
            $serviceClass = $container->getDefinition($id)->getClass();

            if ($serviceClass !== null)
            {
                $this->collectServiceDependencies(
                    serviceClass: $serviceClass,
                    container: $container,
                    servicesReference: $servicesReference
                );

                $serviceIds[] = $serviceClass;

                $servicesReference[\sprintf('%s_service', $serviceClass)] = new ServiceClosureArgument(
                    new Reference($id)
                );
            }
        }

        $container->setParameter(
            name: 'service_bus.services_map',
            value: $serviceIds
        );

        $container
            ->register('service_bus.services_locator', ServiceLocator::class)
            ->setPublic(true)
            ->setArguments([$servicesReference]);
    }

    /**
     * @psalm-param class-string $serviceClass
     *
     * @throws \ReflectionException
     */
    private function collectServiceDependencies(
        string $serviceClass,
        ContainerBuilder $container,
        array &$servicesReference
    ): void {
        $reflectionClass = new \ReflectionClass($serviceClass);

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod)
        {
            foreach ($reflectionMethod->getParameters() as $parameter)
            {
                if ($parameter->hasType() === false)
                {
                    continue;
                }

                /** @var \ReflectionNamedType $reflectionType */
                $reflectionType     = $parameter->getType();
                $reflectionTypeName = $reflectionType->getName();

                if (self::supportedType($parameter) && $container->has($reflectionTypeName))
                {
                    $servicesReference[$reflectionTypeName] = new ServiceClosureArgument(
                        new Reference($reflectionTypeName)
                    );
                }
            }
        }
    }

    private static function supportedType(\ReflectionParameter $parameter): bool
    {
        /** @var \ReflectionNamedType $reflectionType */
        $reflectionType     = $parameter->getType();
        $reflectionTypeName = $reflectionType->getName();

        return (\class_exists($reflectionTypeName) || \interface_exists($reflectionTypeName)) &&
            \is_a($reflectionTypeName, ServiceBusContext::class, true) === false &&
            \is_a($reflectionTypeName, \Throwable::class, true) === false;
    }
}
