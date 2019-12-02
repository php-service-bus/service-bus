<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Bootstrap;

use function ServiceBus\Tests\removeDirectory;
use PHPUnit\Framework\TestCase;
use ServiceBus\Application\Bootstrap;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\GraylogLoggerCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\StdOutLoggerCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;

/**
 *
 */
final class WithLoggersBootstrapTest extends TestCase
{
    private string $cacheDirectory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = \sys_get_temp_dir() . '/bootstrap_test';

        if(\file_exists($this->cacheDirectory) === false)
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
    public function withDotEnv(): void
    {
        $bootstrap = Bootstrap::withDotEnv(__DIR__ . '/valid_dot_env_file.env');

        $bootstrap->useCustomCacheDirectory($this->cacheDirectory);
        $bootstrap->addExtensions(new ServiceBusExtension());
        $bootstrap->addCompilerPasses(
            new TaggedMessageHandlersCompilerPass(),
            new StdOutLoggerCompilerPass(),
            new GraylogLoggerCompilerPass()
        );
        $bootstrap->importParameters(['qwerty' => 'root']);
        $bootstrap->enableAutoImportMessageHandlers([__DIR__]);

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $bootstrap->boot();

        static::assertTrue($container->hasParameter('qwerty'));
        static::assertSame('root', $container->getParameter('qwerty'));

        static::assertSame(\getenv('APP_ENVIRONMENT'), $container->getParameter('service_bus.environment'));
        static::assertSame(\getenv('APP_ENTRY_POINT_NAME'), $container->getParameter('service_bus.entry_point'));
    }
}
