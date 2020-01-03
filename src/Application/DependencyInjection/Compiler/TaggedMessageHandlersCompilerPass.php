<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        $servicesReference = [];
        $serviceIds        = [];

        /**
         * @psalm-var array<string, array<mixed, string>> $taggedServices
         *
         * @var array $taggedServices
         */
        $taggedServices = $container->findTaggedServiceIds('service_bus.service');

        foreach($taggedServices as $id => $tags)
        {
            /** @psalm-var class-string|null $serviceClass */
            $serviceClass = $container->getDefinition($id)->getClass();

            if($serviceClass !== null)
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
     * @psalm-param class-string $serviceClass
     *
     * @param array $servicesReference (passed by reference)
     *
     * @throws \ReflectionException
     */
    private function collectServiceDependencies(string $serviceClass, ContainerBuilder $container, array &$servicesReference): void
    {
        $reflectionClass = new \ReflectionClass($serviceClass);

        foreach($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod)
        {
            foreach($reflectionMethod->getParameters() as $parameter)
            {
                if($parameter->hasType() === false)
                {
                    continue;
                }

                /** @var \ReflectionNamedType $reflectionType */
                $reflectionType     = $parameter->getType();
                $reflectionTypeName = $reflectionType->getName();

                if(self::supportedType($parameter) === true && $container->has($reflectionTypeName) === true)
                {
                    $servicesReference[$reflectionTypeName] = new ServiceClosureArgument(new Reference($reflectionTypeName));
                }
            }
        }
    }

    private static function supportedType(\ReflectionParameter $parameter): bool
    {
        /** @var \ReflectionNamedType $reflectionType */
        $reflectionType     = $parameter->getType();
        $reflectionTypeName = $reflectionType->getName();

        return (\class_exists($reflectionTypeName) === true || \interface_exists($reflectionTypeName) === true) &&
            \is_a($reflectionTypeName, ServiceBusContext::class, true) === false &&
            \is_a($reflectionTypeName, \Throwable::class, true) === false;
    }
}
