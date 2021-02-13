<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Bootstrap;

use PHPUnit\Framework\TestCase;
use ServiceBus\Application\Bootstrap;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\GraylogLoggerCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\StdOutLoggerCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
use ServiceBus\Application\Exceptions\ConfigurationCheckFailed;
use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Tests\Application\Bootstrap\Stubs\TestBootstrapExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function ServiceBus\Tests\removeDirectory;

/**
 *
 */
final class BootstrapTest extends TestCase
{
    /**
     * @var string
     */
    private $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = \sys_get_temp_dir() . '/bootstrap_test';

        if (\file_exists($this->cacheDirectory) === false)
        {
            \mkdir($this->cacheDirectory);
        }
    }

    protected function tearDown(): void
    {
        removeDirectory($this->cacheDirectory);

        unset($this->cacheDirectory);
    }

    /**
     * @test
     */
    public function withEmptyEnEntryPointName(): void
    {
        $this->expectException(ConfigurationCheckFailed::class);
        $this->expectExceptionMessage('Incorrect endpoint name');

        Bootstrap::withEnvironmentValues(__DIR__);
    }

    /**
     * @test
     */
    public function withIncorrectEnvironmentKey(): void
    {
        $this->expectException(ConfigurationCheckFailed::class);
        $this->expectExceptionMessage(
            'Provided incorrect value of the environment: "qwerty". Allowable values: prod, dev, test'
        );

        Bootstrap::create(__DIR__, 'ssss', 'qwerty');
    }

    /**
     * @test
     */
    public function withIncorrectDotEnvPath(): void
    {
        $this->expectException(ConfigurationCheckFailed::class);

        Bootstrap::withDotEnv(__DIR__, __DIR__ . '/qwertyuiop.env');
    }

    /**
     * @test
     */
    public function withIncorrectDotEnvFormat(): void
    {
        $this->expectException(ConfigurationCheckFailed::class);

        Bootstrap::withDotEnv(__DIR__, __FILE__);
    }

    /**
     * @test
     */
    public function withIncorrectRootDirectoryPath(): void
    {
        $this->expectException(ConfigurationCheckFailed::class);

        Bootstrap::create('/dfssf', 'abube', 'dev');
    }

    /**
     * @test
     */
    public function buildWithCorrectParameters(): void
    {
        $bootstrap = Bootstrap::create(__DIR__, 'abube', 'dev');

        $container = $bootstrap->boot();

        self::assertSame(
            'abube',
            $container->getParameter('service_bus.entry_point')
        );

        self::assertSame(
            'dev',
            $container->getParameter('service_bus.environment')
        );
    }

    /**
     * @test
     */
    public function fullConfigure(): void
    {
        $module = new class() implements ServiceBusModule
        {
            public function boot(ContainerBuilder $containerBuilder): void
            {
                $containerBuilder->setParameter('TestModule', 'exists');
            }
        };

        $bootstrap = Bootstrap::withDotEnv(__DIR__, __DIR__ . '/valid_dot_env_file.env');
        $bootstrap->useCustomCacheDirectory($this->cacheDirectory);
        $bootstrap->addExtensions(new ServiceBusExtension());
        $bootstrap->addCompilerPasses(new TaggedMessageHandlersCompilerPass());
        $bootstrap->importParameters(['qwerty' => 'root']);
        $bootstrap->enableAutoImportMessageHandlers([__DIR__ . '/Stubs']);
        $bootstrap->applyModules($module);

        $container = $bootstrap->boot();

        self::assertTrue($container->hasParameter('TestModule'));
        self::assertTrue($container->hasParameter('qwerty'));

        self::assertSame('exists', $container->getParameter('TestModule'));
        self::assertSame('root', $container->getParameter('qwerty'));
    }

    /**
     * @test
     */
    public function withLogger(): void
    {
        $bootstrap = Bootstrap::create(__DIR__, 'withLogger', 'dev');
        $bootstrap->addCompilerPasses(new StdOutLoggerCompilerPass());

        $bootstrap->boot();

        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function withStdOutLogger(): void
    {
        $bootstrap = Bootstrap::create(__DIR__, 'withStdOutLogger', 'dev');
        $bootstrap->addCompilerPasses(new StdOutLoggerCompilerPass());

        $bootstrap->boot();

        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function withGraylogLogger(): void
    {
        $bootstrap = Bootstrap::create(__DIR__, 'withGraylogLogger', 'dev');
        $bootstrap->addCompilerPasses(new GraylogLoggerCompilerPass());

        $bootstrap->boot();

        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function resolveEnvValue(): void
    {
        \putenv("SOME_VALUE=100500");

        $bootstrap = Bootstrap::create(__DIR__, 'tests', 'dev');
        $bootstrap->addExtensions(
            new TestBootstrapExtension()
        );

        $container = $bootstrap->boot();

        self::assertSame(
            '100500',
            $container->getParameter('some_parameter')
        );
    }
}
