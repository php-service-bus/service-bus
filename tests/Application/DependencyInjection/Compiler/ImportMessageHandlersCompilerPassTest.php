<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use ServiceBus\Application\DependencyInjection\Compiler\ImportMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Tests\Stubs\Services\CorrectService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class ImportMessageHandlersCompilerPassTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function process(): void
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter('service_bus.auto_import.handlers_enabled', true);
        $containerBuilder->setParameter('service_bus.auto_import.handlers_directories', [__DIR__ . '/../../../Stubs']);
        $containerBuilder->setParameter('service_bus.auto_import.handlers_excluded', []);

        (new ImportMessageHandlersCompilerPass())->process($containerBuilder);
        (new TaggedMessageHandlersCompilerPass())->process($containerBuilder);

        static::assertTrue($containerBuilder->has('service_bus.services_locator'));
        static::assertTrue($containerBuilder->hasParameter('service_bus.services_map'));

        static::assertCount(1, $containerBuilder->getParameter('service_bus.services_map'));
        static::assertSame([CorrectService::class], $containerBuilder->getParameter('service_bus.services_map'));
    }
}
