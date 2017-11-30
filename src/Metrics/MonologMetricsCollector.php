<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Metrics;

use Monolog\Formatter\ScalarFormatter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Simple out metrics with specified logger
 */
class MonologMetricsCollector implements MetricsCollectorInterface
{
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Log level
     *
     * @var string
     */
    private $logLevel = LogLevel::DEBUG;

    /**
     * Value to string formatter
     *
     * @var ScalarFormatter
     */
    private $valueFormatter;

    /**
     * @param LoggerInterface $logger
     * @param string          $logLevel
     */
    public function __construct(LoggerInterface $logger, $logLevel = LogLevel::DEBUG)
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->valueFormatter = new ScalarFormatter();
    }

    /**
     * @inheritdoc
     */
    public function push(string $type, $value, array $tags = []): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($type, $value, $tags)
            {
                try
                {
                    $this->logger->log(
                        $this->logLevel,
                        \sprintf('%s: %s', $type, $this->formatValue($value)),
                        $tags
                    );

                    $resolve();
                }
                catch(\Throwable $throwable)
                {
                    $reject($throwable);
                }
            }
        );
    }


    /**
     * Format specified value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function formatValue($value)
    {
        $formatted = (string) $this->valueFormatter->format(['data' => $value])['data'];

        if(true === \is_numeric($formatted))
        {
            $formatted = \round($value, 4);
        }

        return $formatted;
    }
}
