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

namespace Desperado\ServiceBus\Application;

use Desperado\ServiceBus\DependencyInjection\Compiler\ImportMessageHandlersCompilerPass;
use Desperado\ServiceBus\DependencyInjection\Compiler\ImportSagasCompilerPass;
use Desperado\ServiceBus\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use Desperado\ServiceBus\DependencyInjection\ContainerBuilder\ContainerBuilder;
use Desperado\ServiceBus\DependencyInjection\Extensions\SchedulerExtension;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\Environment;
use Symfony\Component\Debug\Debug;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
final class Bootstrap
{
    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * Create based on the environment parameters obtained from the ".env" file
     *
     * @param string $envFilePath
     *
     * @return self
     *
     * @throws \Symfony\Component\Dotenv\Exception\FormatException
     * @throws \Symfony\Component\Dotenv\Exception\PathException
     * @throws \LogicException Invalid environment specified
     */
    public static function withDotEnv(string $envFilePath): self
    {
        (new Dotenv())->load($envFilePath);

        return new self();
    }

    /**
     * Create based on environment settings
     *
     * @return self
     *
     * @throws \LogicException Invalid environment specified
     */
    public static function withEnvironmentValues(): self
    {
        return new self();
    }

    /**
     * All sagas from the specified directories will be registered automatically
     *
     * Note: All files containing user-defined functions must be excluded
     * Note: Increases start time because of the need to scan files
     *
     * @param array<int, string> $directories
     * @param array<int, string> $excludedFiles
     *
     * @return self
     */
    public function enableAutoImportSagas(array $directories, array $excludedFiles = []): self
    {
        $this->importParameters([
            'service_bus.auto_import.sagas_enabled'     => true,
            'service_bus.auto_import.sagas_directories' => $directories,
            'service_bus.auto_import.sagas_excluded'    => $excludedFiles
        ]);

        $this->containerBuilder->addCompilerPasses(new ImportSagasCompilerPass());

        return $this;
    }

    /**
     * Enable scheduler (amqp-base)
     *
     * @see https://github.com/mmasiukevich/service-bus/blob/master/doc/scheduler.md
     *
     * @return self
     */
    public function enableScheduler(): self
    {
        $this->containerBuilder->addExtensions(new SchedulerExtension());

        return $this;
    }

    /**
     * All message handlers from the specified directories will be registered automatically
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
            'service_bus.auto_import.handlers_excluded'    => $excludedFiles
        ]);

        $this->containerBuilder->addCompilerPasses(new ImportMessageHandlersCompilerPass());

        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function boot(): ContainerInterface
    {
        $this->containerBuilder->addCompilerPasses(new TaggedMessageHandlersCompilerPass(), new ServiceLocatorTagPass());

        return $this->containerBuilder->build();
    }

    /**
     * Use RabbitMQ transport
     *
     * @param string      $connectionDSN
     * @param string      $endpointExchange
     * @param string|null $endpointRoutingKey
     *
     * @return self
     */
    public function useRabbitMqTransport(
        string $connectionDSN,
        string $endpointExchange,
        ?string $endpointRoutingKey
    ): self
    {
        $this->containerBuilder->addParameters([
            'service_bus.transport.dsn'             => $connectionDSN,
            'service_bus.default_destination_topic' => $endpointExchange,
            'service_bus.default_destination_key'   => $endpointRoutingKey
        ]);

        return $this;
    }

    /**
     * Use SQL storage
     *
     * Possible adapters:
     *
     * -- Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter
     * -- Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter
     *
     * @param string $adapter
     * @param string $connectionDSN
     *
     * @return self
     */
    public function useSqlStorage(string $adapter, string $connectionDSN): self
    {
        $this->containerBuilder->addParameters([
            'service_bus.storage.adapter' => $adapter,
            'service_bus.storage.dsn'     => $connectionDSN

        ]);

        return $this;
    }

    /**
     * Use custom cache directory
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
     * Import parameters to container
     *
     * @param array<string, bool|string|int|float|array<mixed, mixed>|null> $parameters
     *
     * @return self
     */
    public function importParameters(array $parameters): self
    {
        $this->containerBuilder->addParameters($parameters);

        return $this;
    }

    /**
     * @param Extension[] $extensions
     *
     * @return self
     */
    public function addExtensions(Extension ...$extensions): self
    {
        $this->containerBuilder->addExtensions(...$extensions);

        return $this;
    }

    /**
     * @noinspection PhpDocSignatureInspection
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
     *
     * @throws \LogicException
     */
    private function __construct()
    {
        $entryPoint = (string) \getenv('APP_ENTRY_POINT_NAME');
        $envValue   = '' !== (string) \getenv('APP_ENVIRONMENT')
            ? (string) \getenv('APP_ENVIRONMENT')
            : 'dev';

        $this->containerBuilder = new ContainerBuilder($entryPoint, Environment::create($envValue));

        $this->containerBuilder->addParameters([
            'service_bus.environment'     => $envValue,
            'service_bus.entry_point'     => $entryPoint,
            'service_bus.transport.dsn'   => '',
            'service_bus.storage.adapter' => '',
            'service_bus.storage.dsn'     => ''
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
