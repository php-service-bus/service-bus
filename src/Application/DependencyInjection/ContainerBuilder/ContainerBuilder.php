<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Application\DependencyInjection\ContainerBuilder;

use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Environment;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

/**
 * Symfony DI container builder.
 *
 * @internal
 */
final class ContainerBuilder
{
    private const CONTAINER_NAME_TEMPLATE = '%s%sProjectContainer';

    /**
     * Key=>value parameters
     *
     * @psalm-var array<string, bool|string|int|float|array|null>
     *
     * @var array
     */
    private $parameters;

    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $entryPointName;

    /**
     * @see \Symfony\Component\DependencyInjection\Extension\Extension
     *
     * @var \SplObjectStorage
     */
    private $extensions;

    /**
     * @see \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterfac
     *
     * @var \SplObjectStorage
     */
    private $compilerPasses;

    /**
     * @see \ServiceBus\Common\Module\ServiceBusModule
     *
     * @var \SplObjectStorage
     */
    private $modules;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @psalm-var non-empty-string|null
     *
     * @var string|null
     */
    private $cacheDirectory;

    /**
     * ConfigCache caches arbitrary content in files on disk.
     *
     * @var ConfigCache|null
     */
    private $configCache;

    /**
     * @psalm-param non-empty-string $entryPointName
     */
    public function __construct(string $entryPointName, Environment $environment)
    {
        $this->entryPointName = $entryPointName;
        $this->environment    = $environment;
        $this->parameters     = [];

        $this->extensions     = new \SplObjectStorage();
        $this->compilerPasses = new \SplObjectStorage();
        $this->modules        = new \SplObjectStorage();
    }

    /**
     * Add customer compiler pass.
     */
    public function addCompilerPasses(CompilerPassInterface ...$compilerPasses): void
    {
        foreach ($compilerPasses as $compilerPass)
        {
            $this->compilerPasses->attach($compilerPass);
        }
    }

    /**
     * Add customer extension.
     */
    public function addExtensions(Extension ...$extensions): void
    {
        foreach ($extensions as $extension)
        {
            $this->extensions->attach($extension);
        }
    }

    /**
     * Add customer modules.
     */
    public function addModules(ServiceBusModule ...$serviceBusModules): void
    {
        foreach ($serviceBusModules as $serviceBusModule)
        {
            $this->modules->attach($serviceBusModule);
        }
    }

    /**
     * @psalm-param array<string, bool|string|int|float|array|null> $parameters
     */
    public function addParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value)
        {
            $this->parameters[$key] = $value;
        }
    }

    /**
     * Setup cache directory path.
     *
     * @psalm-param non-empty-string $cacheDirectoryPath
     */
    public function setupCacheDirectoryPath(string $cacheDirectoryPath): void
    {
        /** @psalm-var non-empty-string $cacheDirectoryPath */
        $cacheDirectoryPath = \rtrim($cacheDirectoryPath, '/');

        $this->cacheDirectory = $cacheDirectoryPath;
    }

    /**
     * Has compiled actual container.
     */
    public function hasActualContainer(): bool
    {
        if ($this->environment->isDebug() === false)
        {
            return $this->configCache()->isFresh();
        }

        return false;
    }

    /**
     * Receive cached container.
     */
    public function cachedContainer(): ContainerInterface
    {
        /**
         * @psalm-suppress UnresolvableInclude Include generated file
         */
        include_once $this->getContainerClassPath();

        /** @psalm-var class-string<\Symfony\Component\DependencyInjection\Container> $containerClassName */
        $containerClassName = $this->getContainerClassName();

        /**
         * @psalm-suppress UnsafeInstantiation
         * @noinspection   PhpUnnecessaryLocalVariableInspection
         * @var ContainerInterface $container
         */
        $container = new $containerClassName();

        return $container;
    }

    /**
     * Build container.
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @throws \LogicException Cannot dump an uncompiled container
     * @throws \RuntimeException When cache file can't be written
     * @throws \Symfony\Component\DependencyInjection\Exception\EnvParameterException When an env var exists but has
     *                                                                                not been dumped
     * @throws \Throwable Boot module failed
     */
    public function build(): ContainerInterface
    {
        $this->parameters['service_bus.environment'] = $this->environment->toString();
        $this->parameters['service_bus.entry_point'] = $this->entryPointName;

        $containerBuilder = new SymfonyContainerBuilder(new EnvPlaceholderParameterBag($this->parameters));

        /** @var Extension $extension */
        foreach ($this->extensions as $extension)
        {
            $extension->load(
                configs: $this->parameters,
                container: $containerBuilder
            );
        }

        /** @var CompilerPassInterface $compilerPass */
        foreach ($this->compilerPasses as $compilerPass)
        {
            $containerBuilder->addCompilerPass($compilerPass);
        }

        /** @var ServiceBusModule $module */
        foreach ($this->modules as $module)
        {
            $module->boot($containerBuilder);
        }

        $containerBuilder->compile(true);

        $this->dumpContainer($containerBuilder);

        return $this->cachedContainer();
    }

    /**
     * Save container.
     *
     * @throws \LogicException Cannot dump an uncompiled container
     * @throws \RuntimeException When cache file can't be written
     * @throws \Symfony\Component\DependencyInjection\Exception\EnvParameterException When an env var exists but has
     *                                                                                not been dumped
     */
    private function dumpContainer(SymfonyContainerBuilder $builder): void
    {
        $dumper = new PhpDumper($builder);

        $content = $dumper->dump(
            [
                'class'      => $this->getContainerClassName(),
                'base_class' => 'Container',
                'file'       => $this->configCache()->getPath(),
            ]
        );

        if (\is_string($content))
        {
            $this->configCache()->write(
                content: $content,
                metadata: $builder->getResources()
            );
        }
    }

    /**
     * Receive config cache.
     */
    private function configCache(): ConfigCache
    {
        if ($this->configCache === null)
        {
            $this->configCache = new ConfigCache(
                file: $this->getContainerClassPath(),
                debug: $this->environment->isDebug()
            );
        }

        return $this->configCache;
    }

    /**
     * Receive cache directory path.
     */
    private function cacheDirectory(): string
    {
        $cacheDirectory = (string) $this->cacheDirectory;

        if ($cacheDirectory === '' && false === \is_writable($cacheDirectory))
        {
            $cacheDirectory = \sys_get_temp_dir();
        }

        return \rtrim($cacheDirectory, '/');
    }

    /**
     * Get the absolute path to the container class.
     */
    private function getContainerClassPath(): string
    {
        return \sprintf('%s/%s.php', $this->cacheDirectory(), $this->getContainerClassName());
    }

    /**
     * Get container class name.
     */
    private function getContainerClassName(): string
    {
        return \sprintf(
            self::CONTAINER_NAME_TEMPLATE,
            \lcfirst($this->entryPointName),
            \ucfirst($this->environment->toString())
        );
    }
}
