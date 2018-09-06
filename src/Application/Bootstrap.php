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
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\Environment;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Dotenv\Dotenv;

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
     * All sagas from the specified directories will be registered automatically
     * Note: Increases start time because of the need to scan files
     *
     * @param array $directories
     * @param array $excludedSagas Excluded sagas (classes)
     *
     * @return $this
     */
    public function enableAutoImportSagas(array $directories, array $excludedSagas = []): self
    {
        $this->containerBuilder->addCompilerPasses(new ImportSagasCompilerPass($directories, $excludedSagas));

        return $this;
    }

    /**
     * All message handlers from the specified directories will be registered automatically
     * Note: Increases start time because of the need to scan files
     *
     * @param array $directories
     * @param array $excludedClasses
     *
     * @return $this
     */
    public function enableAutoImportMessageHandlers(array $directories, array $excludedClasses): self
    {
        $this->containerBuilder->addCompilerPasses(new ImportMessageHandlersCompilerPass($directories, $excludedClasses));

        return $this;
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
     * @return ContainerInterface
     */
    public function boot(): ContainerInterface
    {
        return $this->containerBuilder->build();
    }

    /**
     * Use amqp-ext transport
     *
     * @param string $connectionDSN
     *
     * @return $this
     */
    public function useAmqpExtTransport(string $connectionDSN): self
    {
        $this->containerBuilder->addParameters(['service_bus.transport.dsn' => $connectionDSN]);

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
     * @return $this
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
     * @return $this
     */
    public function useCustomCacheDirectory(string $cacheDirectoryPath): self
    {
        $this->containerBuilder->setupCacheDirectoryPath($cacheDirectoryPath);

        return $this;
    }

    /**
     * Import parameters to container
     *
     * @param array $parameters
     *
     * @return $this
     */
    public function importParameters(array $parameters): self
    {
        $this->containerBuilder->addParameters($parameters);

        return $this;
    }

    /**
     * @param Extension[] $extensions
     *
     * @return $this
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
     * @return $this
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
        $this->containerBuilder->addCompilerPasses(new TaggedMessageHandlersCompilerPass(), new ServiceLocatorTagPass());
        $this->containerBuilder->addExtensions(new ServiceBusExtension());
    }
}
