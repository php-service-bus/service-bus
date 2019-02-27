<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\DependencyInjection\Compiler\Logger;

use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class LoggerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    public function process(ContainerBuilder $container): void
    {
        $loggerDefinition = $container->getDefinition('service_bus.logger');

        if (NullLogger::class === $loggerDefinition->getClass())
        {
            $loggerDefinition->setClass(Logger::class);
            $loggerDefinition->setArguments(['%service_bus.entry_point%']);
        }

        $processors = [
            PsrLogMessageProcessor::class,
            MemoryUsageProcessor::class,
            ProcessIdProcessor::class,
        ];

        foreach ($processors as $processor)
        {
            $container->addDefinitions([$processor => new Definition($processor)]);
            $loggerDefinition->addMethodCall(
                'pushProcessor',
                [new Reference($processor)]
            );
        }
    }
}
