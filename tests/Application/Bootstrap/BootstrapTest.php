<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Bootstrap;

use PHPUnit\Framework\TestCase;
use ServiceBus\Application\Bootstrap;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
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
     *
     * @throws \Throwable
     */
    public function withDotEnv(): void
    {
        $bootstrap = Bootstrap::withDotEnv(__DIR__ . '/valid_dot_env_file.env');

        $bootstrap->useCustomCacheDirectory($this->cacheDirectory);
        $bootstrap->addExtensions(new ServiceBusExtension());
        $bootstrap->addCompilerPasses(new TaggedMessageHandlersCompilerPass());
        $bootstrap->importParameters(['qwerty' => 'root']);
        $bootstrap->enableAutoImportMessageHandlers([__DIR__]);

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $bootstrap->boot();

        static::assertTrue($container->hasParameter('qwerty'));
        static::assertEquals('root', $container->getParameter('qwerty'));

        static::assertEquals(\getenv('APP_ENVIRONMENT'), $container->getParameter('service_bus.environment'));
        static::assertEquals(\getenv('APP_ENTRY_POINT_NAME'), $container->getParameter('service_bus.entry_point'));
    }
}
