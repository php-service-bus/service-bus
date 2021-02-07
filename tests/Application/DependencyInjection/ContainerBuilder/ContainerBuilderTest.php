<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheDirectory = \sys_get_temp_dir() . '/container_test';

        if (\file_exists($this->cacheDirectory) === false)
        {
            \mkdir($this->cacheDirectory);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        removeDirectory($this->cacheDirectory);

        unset($this->cacheDirectory);
    }

    /**
     * @test
     */
    public function successfulBuildWithDefaultData(): void
    {
        $containerBuilder = new ContainerBuilder('ContainerBuilderTest', Environment::prod());

        $container = $containerBuilder->build();

        self::assertFileExists(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php');
        self::assertFileExists(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php.meta');

        self::assertTrue($containerBuilder->hasActualContainer());

        @\unlink(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php');
        @\unlink(\sys_get_temp_dir() . '/containerBuilderTestProdProjectContainer.php.meta');

        self::assertSame('prod', $container->getParameter('service_bus.environment'));
        self::assertSame('ContainerBuilderTest', $container->getParameter('service_bus.entry_point'));
    }

    /**
     * @test
     */
    public function successfulBuildWithFullConfiguration(): void
    {
        $containerBuilder = new ContainerBuilder('ContainerBuilderTest', Environment::dev());

        self::assertFalse($containerBuilder->hasActualContainer());

        $containerBuilder->setupCacheDirectoryPath($this->cacheDirectory);

        $containerBuilder->addParameters(
            [
                'testing.class' => \get_class($this),
            ]
        );

        $containerBuilder->addExtensions(new ServiceBusExtension(), new TestExtension());

        $containerBuilder->addCompilerPasses(new TestCompilerPass(), new TaggedMessageHandlersCompilerPass());

        $container = $containerBuilder->build();

        self::assertFileExists($this->cacheDirectory . '/containerBuilderTestDevProjectContainer.php');
        self::assertFileExists($this->cacheDirectory . '/containerBuilderTestDevProjectContainer.php.meta');

        self::assertSame(\get_class($this), $container->getParameter('testing.class'));
        self::assertSame('dev', $container->getParameter('service_bus.environment'));
        self::assertSame('ContainerBuilderTest', $container->getParameter('service_bus.entry_point'));

        self::assertTrue($container->has(SomeTestService::class));
        self::assertTrue($container->has(MessageHandlerService::class));

        $someTestService = $container->get(SomeTestService::class);

        /** @see TestCompilerPass::process() */
        self::assertSame('qwerty', $someTestService->env());

        /** @var array<int, string> $messageHandlerServices */
        $messageHandlerServices = $container->getParameter('service_bus.services_map');

        self::assertArrayHasKey(0, $messageHandlerServices);
        self::assertSame(MessageHandlerService::class, $messageHandlerServices[0]);

        self::assertTrue($container->has('service_bus.services_locator'));
    }
}
