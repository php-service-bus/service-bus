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

use Desperado\ServiceBus\Sagas\Saga;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *  All sagas from the specified directories will be registered automatically
 */
final class ImportSagasCompilerPass implements CompilerPassInterface
{
    /**
     * @var array<mixed, string>
     */
    private $directories;

    /**
     * @var array<mixed, string>
     */
    private $excludedSagas;

    /**
     * @param array $directories
     * @param array $excludedSagas
     */
    public function __construct(array $directories, array $excludedSagas)
    {
        $this->directories   = $directories;
        $this->excludedSagas = $excludedSagas;
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        $foundSagas = [];

        foreach(searchFiles($this->directories, '/\.php/i') as $file)
        {
            $class = extractNamespaceFromFile((string) $file);

            if(
                null !== $class &&
                true === \is_a($class, Saga::class, true) &&
                false === \in_array($class, $this->excludedSagas, true)
            )
            {
                $foundSagas[] = $class;
            }
        }

        self::updateParameters($container, $foundSagas);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $foundSagas
     *
     * @return void
     */
    private static function updateParameters(ContainerBuilder $container, array $foundSagas): void
    {
        if(0 !== \count($foundSagas))
        {
            $registeredSagas = true === $container->hasParameter('service_bus.sagas')
                ? $container->getParameter('service_bus.sagas')
                : [];

            $container->setParameter(
                'service_bus.sagas',
                \array_merge($registeredSagas, $foundSagas)
            );
        }
    }
}
