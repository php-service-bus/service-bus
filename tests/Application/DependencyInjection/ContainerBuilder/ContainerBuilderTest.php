<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder;

use function ServiceBus\Tests\removeDirectory;
use PHPUnit\Framework\TestCase;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\ContainerBuilder\ContainerBuilder;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
use ServiceBus\Environment;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\MessageHandlerService;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\SomeTestService;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\TestCompilerPass;
use ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs\TestExtension;

/**
 *
 */
final class ContainerBuilderTest extends TestCase
{
    private string $cacheDirectory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = \sys_get_temp_dir() . '/container_test';

        if (\file_exists($this->cacheDirectory) === false)
        {
            \mkdir($this->cacheDirectory);
        }
    }

    /**
     * {@inheritdoc}
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

        static::assertSame('prod', $container->getParameter('service_bus.environment'));
        static::assertSame('ContainerBuilderTest', $container->getParameter('service_bus.entry_point'));
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function successfulBuildWithFullConfiguration(): void
    {
        $containerBuilder = new ContainerBuilder('ContainerBuilderTest', Environment::dev());

        static::assertFalse($containerBuilder->hasActualContainer());

        $containerBuilder->setupCacheDirectoryPath($this->cacheDirectory);

        $containerBuilder->addParameters(
            [
                'testing.class' => \get_class($this),
            ]
        );

        $containerBuilder->addExtensions(new ServiceBusExtension(), new TestExtension());

        $containerBuilder->addCompilerPasses(new TestCompilerPass(), new TaggedMessageHandlersCompilerPass());

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $containerBuilder->build();

        static::assertFileExists($this->cacheDirectory . '/containerBuilderTestDevProjectContainer.php');
        static::assertFileExists($this->cacheDirectory . '/containerBuilderTestDevProjectContainer.php.meta');

        static::assertSame(\get_class($this), $container->getParameter('testing.class'));
        static::assertSame('dev', $container->getParameter('service_bus.environment'));
        static::assertSame('ContainerBuilderTest', $container->getParameter('service_bus.entry_point'));

        static::assertTrue($container->has(SomeTestService::class));
        static::assertTrue($container->has(MessageHandlerService::class));

        $someTestService = $container->get(SomeTestService::class);

        /** @see TestCompilerPass::process() */
        static::assertSame('qwerty', $someTestService->env());

        /** @var array<int, string> $messageHandlerServices */
        $messageHandlerServices = $container->getParameter('service_bus.services_map');

        static::assertArrayHasKey(0, $messageHandlerServices);
        static::assertSame(MessageHandlerService::class, $messageHandlerServices[0]);

        static::assertTrue($container->has('service_bus.services_locator'));
    }
}
