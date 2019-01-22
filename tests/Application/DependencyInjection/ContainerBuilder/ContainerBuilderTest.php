<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder;

use PHPUnit\Framework\TestCase;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\ContainerBuilder\ContainerBuilder;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
use ServiceBus\Environment;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\MessageHandlerService;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\SomeTestService;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\TestCompilerPass;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\TestExtension;
use function ServiceBus\Tests\removeDirectory;

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
     *
     * @throws \Throwable
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
     *
     * @throws \Throwable
     */
    public function successfulBuildWithFullConfiguration(): void
    {
        $containerBuilder = new ContainerBuilder('ContainerBuilderTest', Environment::dev());

        static::assertFalse($containerBuilder->hasActualContainer());

        $containerBuilder->setupCacheDirectoryPath($this->cacheDirectory);

        $containerBuilder->addParameters([
                'testing.class'               => \get_class($this)
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
    }
}
