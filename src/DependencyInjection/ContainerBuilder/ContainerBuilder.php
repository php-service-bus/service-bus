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

namespace Desperado\ServiceBus\DependencyInjection\ContainerBuilder;

use Desperado\ServiceBus\Environment;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Symfony DI container builder
 */
final class ContainerBuilder
{
    private const CONTAINER_NAME_TEMPLATE = '%s%sProjectContainer';

    /**
     * Parameters
     *
     * @var ContainerParameterCollection
     */
    private $parameters;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Extensions
     *
     * @var ContainerExtensionCollection
     */
    private $extensions;

    /**
     * CompilerPass collection
     *
     * @var ContainerCompilerPassCollection
     */
    private $compilerPasses;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * Cache directory path
     *
     * @var string|null
     */
    private $cacheDirectory;

    /**
     * ConfigCache caches arbitrary content in files on disk
     *
     * @var ConfigCache|null
     */
    private $configCache;

    /**
     * @param string      $entryPointName
     * @param Environment $environment
     */
    public function __construct(string $entryPointName, Environment $environment)
    {
        $this->entryPointName = $entryPointName;
        $this->environment    = $environment;

        $this->parameters     = new ContainerParameterCollection();
        $this->extensions     = new ContainerExtensionCollection();
        $this->compilerPasses = new ContainerCompilerPassCollection();
    }

    /**
     * Add customer compiler pass
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param CompilerPassInterface ...$compilerPasses
     *
     * @return void
     */
    public function addCompilerPasses(CompilerPassInterface ...$compilerPasses): void
    {
        $this->compilerPasses->push(...$compilerPasses);
    }

    /**
     * Add customer extension
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param Extension ...$extensions
     *
     * @return void
     */
    public function addExtensions(Extension ...$extensions): void
    {
        $this->extensions->push(...$extensions);
    }

    /**
     * @param array<string, bool|string|int|float|array<mixed, mixed>|null> $parameters
     *
     * @return void
     */
    public function addParameters(array $parameters): void
    {
        $this->parameters->push($parameters);
    }

    /**
     * Setup cache directory path
     *
     * @param string $cacheDirectoryPath
     *
     * @return void
     */
    public function setupCacheDirectoryPath(string $cacheDirectoryPath): void
    {
        $this->cacheDirectory = \rtrim($cacheDirectoryPath, '/');
    }

    /**
     * Has compiled actual container
     *
     * @return bool
     */
    public function hasActualContainer(): bool
    {
        if(false === $this->environment->isDebug())
        {
            return true === $this->configCache()->isFresh();
        }

        return false;
    }

    /**
     * Receive cached container
     *
     * @return ContainerInterface
     */
    public function cachedContainer(): ContainerInterface
    {
        /**
         * @noinspection   PhpIncludeInspection Include generated file
         * @psalm-suppress UnresolvableInclude Include generated file
         */
        include_once $this->getContainerClassPath();

        /** @var string $containerClassName */
        $containerClassName = $this->getContainerClassName();

        /** @var ContainerInterface $container */
        $container = new $containerClassName();

        return $container;
    }

    /**
     * Build container
     *
     * @return ContainerInterface
     */
    public function build(): ContainerInterface
    {
        $this->parameters->add('service_bus.environment', (string) $this->environment);
        $this->parameters->add('service_bus.entry_point', $this->entryPointName);

        $containerParameters = \iterator_to_array($this->parameters);

        $containerBuilder = new SymfonyContainerBuilder(new ParameterBag($containerParameters));

        /** @var Extension $extension */
        foreach($this->extensions as $extension)
        {
            $extension->load($containerParameters, $containerBuilder);
        }

        /** @var CompilerPassInterface $compilerPass */
        foreach($this->compilerPasses as $compilerPass)
        {
            $containerBuilder->addCompilerPass($compilerPass);
        }

        $containerBuilder->compile();

        $this->dumpContainer($containerBuilder);

        return $this->cachedContainer();
    }

    /**
     * Save container
     *
     * @param SymfonyContainerBuilder $builder
     *
     * @return void
     */
    private function dumpContainer(SymfonyContainerBuilder $builder): void
    {
        $dumper = new PhpDumper($builder);

        $content = $dumper->dump([
                'class'      => $this->getContainerClassName(),
                'base_class' => 'Container',
                'file'       => $this->configCache()->getPath()
            ]
        );

        if(true === \is_string($content))
        {
            $this->configCache()->write($content, $builder->getResources());
        }
    }

    /**
     * Receive config cache
     *
     * @return ConfigCache
     */
    private function configCache(): ConfigCache
    {
        if(null === $this->configCache)
        {
            $this->configCache = new ConfigCache($this->getContainerClassPath(), $this->environment->isDebug());
        }

        return $this->configCache;
    }

    /**
     * Receive cache directory path
     *
     * @return string
     */
    private function cacheDirectory(): string
    {
        $cacheDirectory = (string) $this->cacheDirectory;

        if('' === $cacheDirectory && false === \is_writable($cacheDirectory))
        {
            $cacheDirectory = \sys_get_temp_dir();
        }

        return \rtrim($cacheDirectory, '/');
    }

    /**
     * Get the absolute path to the container class
     *
     * @return string
     */
    private function getContainerClassPath(): string
    {
        return \sprintf('%s/%s.php', $this->cacheDirectory(), $this->getContainerClassName());
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
            \lcfirst($this->entryPointName),
            \ucfirst((string) $this->environment)
        );
    }
}
