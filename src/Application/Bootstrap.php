<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application;

use ServiceBus\Application\DependencyInjection\Compiler\ImportMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\ContainerBuilder\ContainerBuilder;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Environment;
use Symfony\Component\Debug\Debug;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Dotenv\Dotenv;

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
     * @throws \Symfony\Component\Dotenv\Exception\PathException Incorrect .env file path
     * @throws \Symfony\Component\Dotenv\Exception\FormatException Incorrect .env file format
     * @throws \LogicException Incorrect application environment specified
     *
     * @return self
     */
    public static function withDotEnv(string $envFilePath): self
    {
        (new Dotenv())->load($envFilePath);

        return new self();
    }

    /**
     * Create based on environment settings.
     * All parameters must be set in the environment.
     *
     * @see https://github.com/php-service-bus/documentation/blob/master/pages/installation.md#the-list-of-environment-variables
     *
     * @throws \LogicException Incorrect application environment specified
     *
     * @return self
     */
    public static function withEnvironmentValues(): self
    {
        return new self();
    }

    /**
     * Boot custom module.
     *
     * @param ServiceBusModule ...$serviceBusModules
     *
     * @throws \Throwable
     *
     * @return $this
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
     * @param array<int, string> $directories
     * @param array<int, string> $excludedFiles
     *
     * @return self
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
     * Compile container.
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @throws \LogicException Cannot dump an uncompiled container
     * @throws \RuntimeException When cache file can't be written
     * @throws \Symfony\Component\DependencyInjection\Exception\EnvParameterException When an env var exists but has not been dumped
     * @throws \Throwable Boot module failed
     *
     * @return ContainerInterface
     */
    public function boot(): ContainerInterface
    {
        $this->containerBuilder->addCompilerPasses(new TaggedMessageHandlersCompilerPass(), new ServiceLocatorTagPass());

        return $this->containerBuilder->build();
    }

    /**
     * Use custom cache directory.
     * If not specified, the directory for storing temporary files will be used (sys_get_temp_dir).
     *
     * @param string $cacheDirectoryPath
     *
     * @return self
     */
    public function useCustomCacheDirectory(string $cacheDirectoryPath): self
    {
        $this->containerBuilder->setupCacheDirectoryPath($cacheDirectoryPath);

        return $this;
    }

    /**
     * Import parameters to container.
     *
     * @param array<string, array<mixed, mixed>|bool|float|int|string|null> $parameters
     *
     * @return self
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
     *
     * @param Extension ...$extensions
     *
     * @return self
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
     *
     * @param CompilerPassInterface ...$compilerPassInterfaces
     *
     * @return self
     */
    public function addCompilerPasses(CompilerPassInterface ...$compilerPassInterfaces): self
    {
        $this->containerBuilder->addCompilerPasses(...$compilerPassInterfaces);

        return $this;
    }

    /**
     * @throws \LogicException Incorrect application environment specified
     */
    private function __construct()
    {
        $entryPoint = (string) \getenv('APP_ENTRY_POINT_NAME');
        $envValue   = '' !== (string) \getenv('APP_ENVIRONMENT')
            ? (string) \getenv('APP_ENVIRONMENT')
            : 'dev';

        $this->containerBuilder = new ContainerBuilder($entryPoint, Environment::create($envValue));

        $this->containerBuilder->addParameters([
            'service_bus.environment' => $envValue,
            'service_bus.entry_point' => $entryPoint,
        ]);

        $this->containerBuilder->addExtensions(new ServiceBusExtension());

        /**
         * @noinspection ForgottenDebugOutputInspection
         *
         * @todo         : remove SymfonyDebug
         *
         * It is necessary for the correct handling of mistakes concealed by the "@"
         */
        Debug::enable();
    }
}
