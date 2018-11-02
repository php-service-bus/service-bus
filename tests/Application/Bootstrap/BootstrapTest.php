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

namespace Desperado\ServiceBus\Tests\Application\Bootstrap;

use Desperado\ServiceBus\Application\Bootstrap;
use function Desperado\ServiceBus\Common\removeDirectory;
use Desperado\ServiceBus\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class BootstrapTest extends TestCase
{
    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = \sys_get_temp_dir() . '/bootstrap_test';

        if(false === \file_exists($this->cacheDirectory))
        {
            \mkdir($this->cacheDirectory);
        }
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        removeDirectory($this->cacheDirectory);

        unset($this->cacheDirectory);
    }

    /**
     * @test
     *
     * @return void
     */
    public function withDotEnv(): void
    {
        $bootstrap = Bootstrap::withDotEnv(__DIR__ . '/valid_dot_env_file.env');

        $bootstrap->useCustomCacheDirectory($this->cacheDirectory);
        $bootstrap->addExtensions(new ServiceBusExtension());
        $bootstrap->addCompilerPasses(new TaggedMessageHandlersCompilerPass());
        $bootstrap->importParameters(['qwerty' => 'root']);
        $bootstrap->enableAutoImportSagas([__DIR__]);
        $bootstrap->enableAutoImportMessageHandlers([__DIR__]);
        $bootstrap->enableScheduler();

        $bootstrap->useSqlStorage(DoctrineDBALAdapter::class, \getenv('DATABASE_CONNECTION_DSN'));

        $bootstrap->useRabbitMqTransport(
            \getenv('TRANSPORT_CONNECTION_DSN'),
            '',
            ''
        );

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $bootstrap->boot();

        static::assertTrue($container->hasParameter('qwerty'));
        static::assertEquals('root', $container->getParameter('qwerty'));

        static::assertEquals(\getenv('APP_ENVIRONMENT'), $container->getParameter('service_bus.environment'));
        static::assertEquals(\getenv('APP_ENTRY_POINT_NAME'), $container->getParameter('service_bus.entry_point'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function withEnvironmentValues(): void
    {
        \putenv('APP_ENVIRONMENT=test');
        \putenv('APP_ENTRY_POINT_NAME=phpunit');

        $bootstrap = Bootstrap::withEnvironmentValues();

        $bootstrap->useCustomCacheDirectory($this->cacheDirectory);
        $bootstrap->addExtensions(new ServiceBusExtension());
        $bootstrap->addCompilerPasses(new TaggedMessageHandlersCompilerPass());
        $bootstrap->importParameters(['qwerty1' => 'root1']);

        $bootstrap->useSqlStorage(DoctrineDBALAdapter::class, \getenv('DATABASE_CONNECTION_DSN'));
        $bootstrap->useRabbitMqTransport(
            \getenv('TRANSPORT_CONNECTION_DSN'), '',''
        );

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $bootstrap->boot();

        static::assertTrue($container->hasParameter('qwerty1'));
        static::assertEquals('root1', $container->getParameter('qwerty1'));

        static::assertEquals(\getenv('APP_ENVIRONMENT'), $container->getParameter('service_bus.environment'));
        static::assertEquals(\getenv('APP_ENTRY_POINT_NAME'), $container->getParameter('service_bus.entry_point'));
    }
}
