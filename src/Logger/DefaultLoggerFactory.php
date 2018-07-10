<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Logger;

use Desperado\ServiceBus\Environment;
use Monolog\Logger;
use Monolog\Processor as MonologProcessors;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Desperado\ServiceBus\Logger\Processors\ExtraDataProcessor;
use Desperado\ServiceBus\Logger\Processors\HostNameProcessor;

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
     *
     * @return Logger
     */
    public static function build(string $entryPointName, Environment $environment): Logger
    {
        $self                 = new self();
        $self->entryPointName = $entryPointName;
        $self->environment    = $environment;

        return new Logger($entryPointName, $self->getHandlers(), $self->getProcessors());
    }

    /**
     * Get log handlers collection
     *
     * @return \Monolog\Handler\AbstractProcessingHandler[]
     */
    private function getHandlers(): array
    {
        $logStreamHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
        $logStreamHandler->setFormatter(new ConsoleFormatter());

        return [$logStreamHandler];
    }

    /**
     * Get processors
     *
     * @return array
     */
    private function getProcessors(): array
    {
        /**
         * Cant throw exception (already validated)
         *
         * @noinspection ExceptionsAnnotatingAndHandlingInspection
         */
        return [
            new MonologProcessors\ProcessIdProcessor(),
            new MonologProcessors\PsrLogMessageProcessor(),
            new MonologProcessors\MemoryUsageProcessor(),
            new HostNameProcessor(),
            new ExtraDataProcessor([
                'entry_point' => $this->entryPointName,
                'env'         => (string) $this->environment
            ])
        ];
    }
}
