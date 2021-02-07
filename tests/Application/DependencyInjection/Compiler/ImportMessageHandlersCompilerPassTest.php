<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use ServiceBus\Application\DependencyInjection\Compiler\ImportMessageHandlersCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\TaggedMessageHandlersCompilerPass;
use ServiceBus\Tests\Application\DependencyInjection\Compiler\Stubs\CorrectService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class ImportMessageHandlersCompilerPassTest extends TestCase
{
    /**
     * @test
     */
    public function process(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('service_bus.auto_import.handlers_enabled', true);
        $containerBuilder->setParameter('service_bus.auto_import.handlers_directories', [__DIR__ . '/Stubs']);
        $containerBuilder->setParameter('service_bus.auto_import.handlers_excluded', []);

        (new ImportMessageHandlersCompilerPass())->process($containerBuilder);
        (new TaggedMessageHandlersCompilerPass())->process($containerBuilder);

        self::assertTrue($containerBuilder->has('service_bus.services_locator'));
        self::assertTrue($containerBuilder->hasParameter('service_bus.services_map'));
        self::assertCount(1, (array) $containerBuilder->getParameter('service_bus.services_map'));
        self::assertSame([CorrectService::class], $containerBuilder->getParameter('service_bus.services_map'));
    }
}
