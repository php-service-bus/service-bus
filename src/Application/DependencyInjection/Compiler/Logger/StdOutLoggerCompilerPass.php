<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\DependencyInjection\Compiler\Logger;

use Monolog\Logger;
use ServiceBus\Infrastructure\Logger\Handlers\StdOut\StdOutHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Std out logger support
 */
final class StdOutLoggerCompilerPass implements CompilerPassInterface
{
    /**
     * @var int
     */
    private $logLevel;

    /**
     * @param int $logLevel
     */
    public function __construct(int $logLevel = Logger::DEBUG)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('service_bus.logger.echo.log_level', $this->logLevel);

        $container->addDefinitions([
                StdOutHandler::class => (new Definition(StdOutHandler::class))->setArguments(['%service_bus.logger.echo.log_level%'])
            ]
        );

        (new LoggerCompilerPass)->process($container);

        $loggerDefinition = $container->getDefinition('service_bus.logger');

        $loggerDefinition->addMethodCall(
            'pushHandler',
            [new Reference(StdOutHandler::class)]
        );
    }
}
