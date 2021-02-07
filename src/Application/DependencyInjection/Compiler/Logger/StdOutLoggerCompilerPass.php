<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Application\DependencyInjection\Compiler\Logger;

use Monolog\Logger;
use ServiceBus\Infrastructure\Logger\Handlers\StdOut\StdOutHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Std out logger support.
 */
final class StdOutLoggerCompilerPass implements CompilerPassInterface
{
    /**
     * @var int
     */
    private $logLevel;

    public function __construct(int $logLevel = Logger::DEBUG)
    {
        $this->logLevel = $logLevel;
    }

    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('service_bus.logger.echo.log_level', $this->logLevel);

        $definition = (new Definition(StdOutHandler::class))
            ->setArguments(['%service_bus.logger.echo.log_level%']);

        $container->addDefinitions([StdOutHandler::class => $definition]);

        (new LoggerCompilerPass())->process($container);

        $loggerDefinition = $container->getDefinition('service_bus.logger');

        $loggerDefinition->addMethodCall(
            method: 'pushHandler',
            arguments: [new Reference(StdOutHandler::class)]
        );
    }
}
