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

namespace Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder;

use function Desperado\ServiceBus\Common\removeDirectory;
use Desperado\ServiceBus\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use Desperado\ServiceBus\DependencyInjection\ContainerBuilder\ContainerBuilder;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\Environment;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder\Stubs\MessageHandlerService;
use Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder\Stubs\SomeTestService;
use Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder\Stubs\TestCompilerPass;
use Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder\Stubs\TestExtension;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class ContainerBuilderTest extends TestCase
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

        $this->cacheDirectory = \sys_get_temp_dir() . '/container_test';

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
    public function successfulBuildWithDefaultData(): void
    {
        $containerBuilder = new ContainerBuilder('ContainerBuilderTest', Environment::prod());

        static::assertFalse($containerBuilder->hasActualContainer());

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $containerBuilder->build();

        static::assertFileExists(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php');
        static::assertFileExists(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php.meta');

        static::assertTrue($containerBuilder->hasActualContainer());

        @\unlink(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php');
        @\unlink(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php.meta');

        static::assertEquals('prod', $container->getParameter('service_bus.environment'));
        static::assertEquals('ContainerBuilderTest', $container->getParameter('service_bus.entry_point'));


    }

    /**
     * @test
     *
     * @return void
     */
    public function successfulBuildWithFullConfiguration(): void
    {
        $containerBuilder = new ContainerBuilder('ContainerBuilderTest', Environment::dev());

        static::assertFalse($containerBuilder->hasActualContainer());

        $containerBuilder->setupCacheDirectoryPath($this->cacheDirectory);

        $containerBuilder->addParameters([
                'testing.class'               => \get_class($this),
                'service_bus.transport.dsn'   => 'amqp://user:password@host:port',
                'service_bus.storage.adapter' => DoctrineDBALAdapter::class,
                'service_bus.storage.dsn'     => ''
            ]
        );

        $containerBuilder->addExtensions(new ServiceBusExtension(), new TestExtension());

        $containerBuilder->addCompilerPasses(new TestCompilerPass(), new TaggedMessageHandlersCompilerPass());

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $containerBuilder->build();

        static::assertFileExists($this->cacheDirectory . '/containerBuilderTestDevProjectContainer.php');
        static::assertFileExists($this->cacheDirectory . '/containerBuilderTestDevProjectContainer.php.meta');

        static::assertEquals(\get_class($this), $container->getParameter('testing.class'));
        static::assertEquals('dev', $container->getParameter('service_bus.environment'));
        static::assertEquals('ContainerBuilderTest', $container->getParameter('service_bus.entry_point'));

        static::assertTrue($container->has(SomeTestService::class));
        static::assertTrue($container->has(MessageHandlerService::class));

        $someTestService = $container->get(SomeTestService::class);

        /** @see TestCompilerPass::process() */
        static::assertEquals('qwerty', $someTestService->env());

        /** @var array<int, string> $messageHandlerServices */
        $messageHandlerServices = $container->getParameter('service_bus.services_map');

        static::assertArrayHasKey(0, $messageHandlerServices);
        static::assertEquals(MessageHandlerService::class, $messageHandlerServices[0]);

        static::assertTrue($container->has('service_bus.services_locator'));

        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $container->get('service_bus.services_locator');

        /** @see MessageHandlerService::someHandler args */
        static::assertTrue($serviceLocator->has(SagaProvider::class));
        static::assertTrue($serviceLocator->has(\sprintf('%s_service', MessageHandlerService::class)));
    }
}
