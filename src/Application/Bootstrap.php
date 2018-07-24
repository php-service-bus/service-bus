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

use Desperado\ServiceBus\DependencyInjection\Compiler\ServicesCompilerPass;
use Desperado\ServiceBus\DependencyInjection\ContainerBuilder\ContainerBuilder;
use Desperado\ServiceBus\DependencyInjection\Extensions\AmqpExtTransportExtension;
use Desperado\ServiceBus\DependencyInjection\Extensions\DefaultStorageExtension;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\Environment;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Dotenv\Dotenv;

/**
 *
 */
final class Bootstrap
{
    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * @var ContainerInterface|null
     */
    private $container;

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
     * Use amqp-ext transport
     *
     * @param string $connectionDSN
     *
     * @return void
     */
    public function useAmqpExtTransport(string $connectionDSN): void
    {
        $this->containerBuilder->addParameters([
            'transport' => [
                'dsn' => $connectionDSN
            ]
        ]);

        $this->containerBuilder->addExtensions(new AmqpExtTransportExtension());
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
     * @return void
     */
    public function useSqlStorage(string $adapter, string $connectionDSN): void
    {
        $this->containerBuilder->addParameters([
            'storage' => [
                'adapter' => $adapter,
                'dsn'     => $connectionDSN
            ]
        ]);

        $this->containerBuilder->addExtensions(new DefaultStorageExtension());
    }

    /**
     * Import parameters to container
     *
     * @param array $parameters
     *
     * @return void
     */
    public function importParameters(array $parameters): void
    {
        $this->containerBuilder->addParameters($parameters);
    }

    /**
     * @param Extension ...$extensions
     *
     * @return void
     */
    public function addExtensions(Extension ...$extensions): void
    {
        $this->containerBuilder->addExtensions(...$extensions);
    }

    /**
     * @param CompilerPassInterface ...$compilerPassInterfaces
     *
     * @return void
     */
    public function addCompilerPasses(CompilerPassInterface ...$compilerPassInterfaces): void
    {
        $this->containerBuilder->addCompilerPasses(...$compilerPassInterfaces);
    }

    /**
     *
     * @throws \LogicException
     */
    private function __construct()
    {
        $envValue = '' !== (string) \getenv('APP_ENVIRONMENT')
            ? (string) \getenv('APP_ENVIRONMENT')
            : 'dev';

        $this->environment      = Environment::create($envValue);
        $this->containerBuilder = new ContainerBuilder(
            (string) \getenv('APP_ENTRY_POINT_NAME'),
            $this->environment
        );

        $this->containerBuilder->addCompilerPasses(new ServicesCompilerPass());
        $this->containerBuilder->addExtensions(new ServiceBusExtension());
    }
}
