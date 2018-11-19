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

namespace Desperado\ServiceBus\Infrastructure\Logger;

use Desperado\ServiceBus\Environment;
use Monolog\Logger;
use Monolog\Processor as MonologProcessors;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Psr\Log\LogLevel;

/**
 * Create default logger instance
 */
final class DefaultLoggerFactory
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Build logger
     *
     * @param string      $entryPointName
     * @param Environment $environment
     * @param string      $logLevel
     *
     * @return Logger
     */
    public static function build(
        string $entryPointName,
        Environment $environment,
        string $logLevel = LogLevel::DEBUG
    ): Logger
    {
        $self                 = new self();
        $self->entryPointName = $entryPointName;
        $self->environment    = $environment;

        return new Logger($entryPointName, $self->getHandlers($logLevel), $self->getProcessors());
    }

    /**
     * Get log handlers collection
     *
     * @param string $logLevel
     *
     * @return \Monolog\Handler\AbstractProcessingHandler[]
     */
    private function getHandlers(string $logLevel): array
    {
        $logStreamHandler = new StreamHandler(new ResourceOutputStream(\STDOUT, 65000), $logLevel);
        $logStreamHandler->setFormatter(new ConsoleFormatter());

        return [$logStreamHandler];
    }

    /**
     * Get processors
     *
     * @return array<mixed, callable>
     */
    private function getProcessors(): array
    {
        return [
            new MonologProcessors\ProcessIdProcessor(),
            new MonologProcessors\PsrLogMessageProcessor(),
            new MonologProcessors\MemoryUsageProcessor(),
            new MonologProcessors\TagProcessor([
                'entryPoint' => $this->entryPointName,
                'env'        => (string) $this->environment
            ])
        ];
    }
}
