<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Application\DependencyInjection\Compiler\Logger;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ServiceBus\Infrastructure\Logger\Handlers\Graylog\UdpHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Graylog logger support.
 */
final class GraylogLoggerCompilerPass implements CompilerPassInterface
{
    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $host;

    /**
     * @psalm-var positive-int
     *
     * @var int
     */
    private $port;

    /**
     * @psalm-var Logger::DEBUG | Logger::INFO | Logger::NOTICE | Logger::WARNING | Logger::ERROR | Logger::CRITICAL | Logger::ALERT | Logger::EMERGENCY
     *
     * @var int
     */
    private $logLevel;

    /**
     * @var bool
     */
    private $gzipMessage;

    /**
     * @psalm-param non-empty-string $host
     * @psalm-param positive-int $port
     * @psalm-param Logger::DEBUG | Logger::INFO | Logger::NOTICE | Logger::WARNING | Logger::ERROR | Logger::CRITICAL | Logger::ALERT | Logger::EMERGENCY $logLevel
     */
    public function __construct(
        string $host = '0.0.0.0',
        int $port = 514,
        int $logLevel = Logger::DEBUG,
        bool $gzipMessage = false
    ) {
        $this->host        = $host;
        $this->port        = $port;
        $this->logLevel    = $logLevel;
        $this->gzipMessage = $gzipMessage;
    }

    public function process(ContainerBuilder $container): void
    {
        $this->injectParameters($container);

        $handlerDefinition = new Definition(UdpHandler::class, [
            '%service_bus.logger.graylog.udp_host%',
            '%service_bus.logger.graylog.udp_port%',
            '%service_bus.logger.graylog.gzip%',
            '%service_bus.logger.graylog.log_level%',
        ]);

        $container->addDefinitions([UdpHandler::class => $handlerDefinition]);

        (new LoggerCompilerPass())->process($container);

        $loggerDefinition = $container->getDefinition(LoggerInterface::class);

        $loggerDefinition->addMethodCall(
            method: 'pushHandler',
            arguments: [new Reference(UdpHandler::class)]
        );
    }

    private function injectParameters(ContainerBuilder $containerBuilder): void
    {
        $parameters = [
            'service_bus.logger.graylog.udp_host'  => $this->host,
            'service_bus.logger.graylog.udp_port'  => $this->port,
            'service_bus.logger.graylog.gzip'      => $this->gzipMessage,
            'service_bus.logger.graylog.log_level' => $this->logLevel,
        ];

        foreach ($parameters as $key => $value)
        {
            $containerBuilder->setParameter(
                name: $key,
                value: $value
            );
        }
    }
}
