<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Bootstrap;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection as DependencyInjection;

/**
 * Dependency injection container builder
 */
final class ContainerBuilder
{
    private const CONTAINER_NAME_TEMPLATE = 'serviceBus%sProjectContainer';

    private const CONTAINER_PARAMETER_ENTRY_POINT_NAME = 'service_bus.entry_point';
    private const CONTAINER_PARAMETER_ROOT_DIR_NAME = 'service_bus.root_dir';
    private const CONTAINER_PARAMETER_CACHE_DIR_NAME = 'service_bus.cache_dir';
    private const CONTAINER_PARAMETER_DEBUG_FLAG_NAME = 'service_bus.is_debug_env';
    private const CONTAINER_PARAMETER_SERVICES_RELATION_NAME = 'service_bus.services_relations';

    /**
     * Application configuration
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * Root application path
     *
     * @var string
     */
    private $rootDirectoryPath;

    /**
     * Cache directory path
     *
     * @var string
     */
    private $cacheDirectoryPath;

    /**
     * Container parameters
     *
     * @var DependencyInjection\ParameterBag\ParameterBag
     */
    private $containerParametersBag;

    /**
     * Compilation passes
     *
     * @var DependencyInjection\Compiler\CompilerPassInterface[]
     */
    private $compilerPassCollection;

    /**
     * Extensions
     *
     * @var DependencyInjection\Extension\Extension[]
     */
    private $extensionCollection;

    /**
     * Container class name
     *
     * @var string
     */
    private $containerClassName;

    /**
     * Absolute path to the container class
     *
     * @var string
     */
    private $containerClassPath;

    /**
     * @param Configuration $configuration
     * @param string        $rootDirectoryPath
     * @param string        $cacheDirectoryPath
     */
    public function __construct(Configuration $configuration, string $rootDirectoryPath, string $cacheDirectoryPath)
    {
        $this->configuration = $configuration;
        $this->rootDirectoryPath = $rootDirectoryPath;
        $this->cacheDirectoryPath = $cacheDirectoryPath;

        $this->containerParametersBag = $this->getDefaultContainerParameters();
        $this->containerClassName = $this->getContainerClassName();
        $this->containerClassPath = $this->getContainerClassPath();

        $this->compilerPassCollection = [];
        $this->extensionCollection = [];
    }

    /**
     * Checks if the cache is still fresh
     *
     * @return bool
     */
    public function isCacheActual(): bool
    {
        return true === $this->exists() && $this->createConfigCache()->isFresh();
    }

    /**
     * Does the container class exist?
     *
     * @return bool
     */
    public function exists(): bool
    {
        return true === \file_exists($this->getContainerClassPath());
    }

    /**
     * Rebuild container
     *
     * @return ContainerInterface
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\EnvParameterException
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function rebuild(): ContainerInterface
    {
        $configCache = $this->createConfigCache();
        $configData = $this->configuration->toArray();

        $this->addContainerParameters($configData);

        $containerBuilder = new DependencyInjection\ContainerBuilder($this->containerParametersBag);

        foreach($this->extensionCollection as $extension)
        {
            $extension->load($configData, $containerBuilder);
        }

        foreach($this->compilerPassCollection as $compilerPass)
        {
            $containerBuilder->addCompilerPass($compilerPass);
        }

        $containerBuilder->setParameter(
            self::CONTAINER_PARAMETER_SERVICES_RELATION_NAME,
            $this->extractServicesClassRelation($containerBuilder)
        );

        $containerBuilder->compile();

        $this->dumpContainer($configCache, $containerBuilder, $this->getContainerClassName());

        return $this->getContainer();
    }

    /**
     * Get compiled container
     *
     * @return ContainerInterface
     *
     * @throws \Throwable
     */
    public function getContainer(): ContainerInterface
    {
        /** @noinspection PhpIncludeInspection */
        include $this->containerClassPath;

        $containerClassName = $this->containerClassName;

        return new $containerClassName();
    }

    /**
     * Add container parameters
     *
     * @param array $parameters
     *
     * @return void
     */
    public function addContainerParameters(array $parameters): void
    {
        foreach($parameters as $key => $value)
        {
            $this->addContainerParameter($key, $value);
        }
    }

    /**
     * Add container parameter
     *
     * @param string                   $key
     * @param array|null|string|object $value
     *
     * @return void
     */
    public function addContainerParameter(string $key, $value): void
    {
        $this->containerParametersBag->set($key, $value);
    }

    /**
     * Add extension collection
     *
     * @param array $extensionCollection
     *
     * @return void
     */
    public function addExtensions(array $extensionCollection): void
    {
        foreach($extensionCollection as $extension)
        {
            $this->addExtension($extension);
        }
    }

    /**
     * Add extension
     *
     * @param DependencyInjection\Extension\Extension $extension
     *
     * @return void
     */
    public function addExtension(DependencyInjection\Extension\Extension $extension): void
    {
        $this->extensionCollection[\spl_object_hash($extension)] = $extension;
    }

    /**
     * Add compilation passes
     *
     * @param DependencyInjection\Compiler\CompilerPassInterface[] $compilerPassCollection
     *
     * @return void
     */
    public function addCompilationPasses(array $compilerPassCollection): void
    {
        foreach($compilerPassCollection as $compilerPass)
        {
            $this->addCompilationPass($compilerPass);
        }
    }

    /**
     * Add compilation pass
     *
     * @param DependencyInjection\Compiler\CompilerPassInterface $compilerPass
     *
     * @return void
     */
    public function addCompilationPass(DependencyInjection\Compiler\CompilerPassInterface $compilerPass): void
    {
        $this->compilerPassCollection[\spl_object_hash($compilerPass)] = $compilerPass;
    }

    /**
     * Getting the relation of the class with the service identifier
     *
     * @param DependencyInjection\ContainerBuilder $builder
     *
     * @return array
     */
    private function extractServicesClassRelation(DependencyInjection\ContainerBuilder $builder): array
    {
        $relations = \array_filter(
            \array_map(
                function(string $eachService) use ($builder)
                {
                    if(true === $builder->hasDefinition($eachService))
                    {
                        return ['class' => $builder->getDefinition($eachService)->getClass(), 'id' => $eachService];
                    }

                    return null;
                },
                $builder->getServiceIds()
            )
        );

        $result = [];

        foreach($relations as $relation)
        {
            $result[$relation['class']] = $relation['id'];
        }

        return $result;
    }

    /**
     * Dumps the service container to PHP code in the cache
     *
     * @param ConfigCache                          $cache
     * @param DependencyInjection\ContainerBuilder $container
     * @param string                               $class
     *
     * @return void
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\EnvParameterException
     * @throws \RuntimeException
     */
    private function dumpContainer(
        ConfigCache $cache,
        DependencyInjection\ContainerBuilder $container,
        string $class
    ): void
    {
        $dumper = new DependencyInjection\Dumper\PhpDumper($container);
        $content = (string) $dumper->dump([
                'class'      => $class,
                'base_class' => 'Container',
                'file'       => $cache->getPath()
            ]
        );

        $cache->write($content, $container->getResources());
    }

    /**
     * Create configCache caches arbitrary content in files on disk
     *
     * @return ConfigCache
     */
    private function createConfigCache(): ConfigCache
    {
        return new ConfigCache(
            $this->containerClassPath,
            $this->configuration->getEnvironment()->isDebug()
        );
    }

    /**
     * Get the absolute path to the container class
     *
     * @return string
     */
    private function getContainerClassPath(): string
    {
        return \sprintf('%s/%s.php', $this->cacheDirectoryPath, $this->containerClassName);
    }

    /**
     * Get container class name
     *
     * @return string
     */
    private function getContainerClassName(): string
    {
        return \sprintf(
            self::CONTAINER_NAME_TEMPLATE,
            \ucfirst((string) $this->configuration->getEnvironment())
        );
    }

    /**
     * Get base application container parameters
     *
     * @return DependencyInjection\ParameterBag\ParameterBag
     */
    private function getDefaultContainerParameters(): DependencyInjection\ParameterBag\ParameterBag
    {
        return new DependencyInjection\ParameterBag\ParameterBag([
            self::CONTAINER_PARAMETER_ENTRY_POINT_NAME => $this->configuration->getEntryPointName(),
            self::CONTAINER_PARAMETER_ROOT_DIR_NAME    => $this->rootDirectoryPath,
            self::CONTAINER_PARAMETER_CACHE_DIR_NAME   => $this->cacheDirectoryPath,
            self::CONTAINER_PARAMETER_DEBUG_FLAG_NAME  => $this->configuration->getEnvironment()->isDebug()
        ]);
    }
}
