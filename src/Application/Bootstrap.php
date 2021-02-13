<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Application;

use ServiceBus\Application\DependencyInjection\Compiler\ImportMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\Retry\SimpleRetryCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\ContainerBuilder\ContainerBuilder;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
use ServiceBus\Application\Exceptions\ConfigurationCheckFailed;
use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;

/**
 * Initial application initialization: loading the main components and compiling the dependency container.
 */
final class Bootstrap
{
    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * Create based on the environment parameters obtained from the ".env" file (via symfony/dotenv component).
     *
     * @param string $envFilePath Absolute path to .env file
     *
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Incorrect root directory path
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Incorrect .env file path
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Incorrect .env file format
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Empty entry point name
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Empty/incorrect environment value
     */
    public static function withDotEnv(string $rootDirectoryPath, string $envFilePath): self
    {
        try
        {
            (new Dotenv())
                ->usePutenv(true)
                ->load($envFilePath);
        }
        catch (\Throwable $throwable)
        {
            throw new ConfigurationCheckFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }

        return self::withEnvironmentValues($rootDirectoryPath);
    }

    /**
     * Create based on environment settings.
     * All parameters must be set in the environment.
     *
     * @see https://github.com/php-service-bus/documentation/blob/master/pages/installation.md#the-list-of-environment-variables
     *
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Incorrect root directory path
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Empty entry point name
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Empty/incorrect environment value
     */
    public static function withEnvironmentValues(string $rootDirectoryPath): self
    {
        $entryPoint     = \getenv('APP_ENTRY_POINT_NAME');
        $appEnvironment = \getenv('APP_ENVIRONMENT');

        return self::create(
            rootDirectoryPath: $rootDirectoryPath,
            entryPointName: \is_string($entryPoint)
            ? $entryPoint
            : throw new ConfigurationCheckFailed('Incorrect endpoint name'),
            environment: \is_string($appEnvironment)
            ? $appEnvironment
            : throw new ConfigurationCheckFailed('Incorrect env'),
        );
    }

    /**
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Incorrect root directory path
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Empty entry point name
     * @throws \ServiceBus\Application\Exceptions\ConfigurationCheckFailed Empty/incorrect environment value
     */
    public static function create(string $rootDirectoryPath, string $entryPointName, string $environment): self
    {
        if ($entryPointName === '')
        {
            throw ConfigurationCheckFailed::emptyEntryPointName();
        }

        try
        {
            $env = Environment::create($environment);
        }
        catch (\Throwable $throwable)
        {
            throw new ConfigurationCheckFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }

        return new self(
            rootDirectoryPath: $rootDirectoryPath,
            entryPointName: $entryPointName,
            environment: $env
        );
    }

    /**
     * Boot custom module.
     *
     * @throws \Throwable
     */
    public function applyModules(ServiceBusModule ...$serviceBusModules): self
    {
        $this->containerBuilder->addModules(...$serviceBusModules);

        return $this;
    }

    /**
     * All message handlers from the specified directories will be registered automatically.
     *
     * Note: All files containing user-defined functions must be excluded
     * Note: Increases start time because of the need to scan files
     *
     * @psalm-param array<int, string> $directories
     * @psalm-param array<int, string> $excludedFiles
     */
    public function enableAutoImportMessageHandlers(array $directories, array $excludedFiles = []): self
    {
        $this->importParameters([
            'service_bus.auto_import.handlers_enabled'     => true,
            'service_bus.auto_import.handlers_directories' => $directories,
            'service_bus.auto_import.handlers_excluded'    => $excludedFiles,
        ]);

        $this->containerBuilder->addCompilerPasses(new ImportMessageHandlersCompilerPass());

        return $this;
    }

    /**
     * Enable simple message retrying strategy
     *
     * @var int $retryDelay Retry interval (in seconds)
     */
    public function enableSimpleRetryStrategy(int $maxRetryCount = 10, int $retryDelay = 3): self
    {
        $this->containerBuilder->addCompilerPasses(
            new SimpleRetryCompilerPass(
                maxRetryCount: $maxRetryCount,
                retryDelay: $retryDelay
            )
        );

        return $this;
    }

    /**
     * Compile container.
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @throws \LogicException Cannot dump an uncompiled container
     * @throws \RuntimeException When cache file can't be written
     * @throws \Symfony\Component\DependencyInjection\Exception\EnvParameterException When an env var exists but has
     *                                                                                not been dumped
     * @throws \Throwable Boot module failed
     */
    public function boot(): ContainerInterface
    {
        $this->containerBuilder->addCompilerPasses(
            new TaggedMessageHandlersCompilerPass(),
            new ServiceLocatorTagPass()
        );

        return $this->containerBuilder->build();
    }

    /**
     * Use custom cache directory.
     * If not specified, the directory for storing temporary files will be used (sys_get_temp_dir).
     */
    public function useCustomCacheDirectory(string $cacheDirectoryPath): self
    {
        $this->containerBuilder->setupCacheDirectoryPath($cacheDirectoryPath);

        return $this;
    }

    /**
     * Import parameters to container.
     *
     * @psalm-param array<string, array<mixed, mixed>|bool|float|int|string|null> $parameters
     */
    public function importParameters(array $parameters): self
    {
        $this->containerBuilder->addParameters($parameters);

        return $this;
    }

    /**
     * Registers custom extensions.
     *
     * @see https://symfony.com/doc/current/bundles/extension.html
     */
    public function addExtensions(Extension ...$extensions): self
    {
        $this->containerBuilder->addExtensions(...$extensions);

        return $this;
    }

    /**
     * Registers custom compiler passes.
     *
     * @see https://symfony.com/doc/current/service_container/compiler_passes.html
     */
    public function addCompilerPasses(CompilerPassInterface ...$compilerPassInterfaces): self
    {
        $this->containerBuilder->addCompilerPasses(...$compilerPassInterfaces);

        return $this;
    }

    private function __construct(string $rootDirectoryPath, string $entryPointName, Environment $environment)
    {
        if (\is_dir($rootDirectoryPath) === false || \is_readable($rootDirectoryPath) === false)
        {
            throw new ConfigurationCheckFailed('Incorrect root directory path');
        }

        $this->containerBuilder = new ContainerBuilder(
            entryPointName: $entryPointName,
            environment: $environment
        );

        $this->containerBuilder->addParameters([
            'service_bus.environment'    => $environment->toString(),
            'service_bus.entry_point'    => $entryPointName,
            'service_bus.root_directory' => $rootDirectoryPath
        ]);

        $this->containerBuilder->addExtensions(new ServiceBusExtension());

        /**
         * @todo: remove SymfonyDebug
         *
         * It is necessary for the correct handling of mistakes concealed by the "@"
         */
        Debug::enable();
    }
}
