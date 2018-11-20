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

namespace Desperado\ServiceBus\Tests\DependencyInjection\Compiler;

use Desperado\ServiceBus\DependencyInjection\Compiler\ImportSagasCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class ImportSagasCompilerPassTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function load(): void
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter('service_bus.auto_import.sagas_enabled', true);
        $containerBuilder->setParameter('service_bus.auto_import.sagas_directories', [__DIR__ . '/../../Stubs']);
        $containerBuilder->setParameter('service_bus.auto_import.sagas_excluded', []);

        $compiler = new ImportSagasCompilerPass();
        $compiler->process($containerBuilder);

        /** @var array<int, string> $registeredSagas */
        $registeredSagas = $containerBuilder->getParameter('service_bus.sagas_map');

        static::assertNotEmpty($registeredSagas);
        static::assertCount(2, $registeredSagas);
    }
}
