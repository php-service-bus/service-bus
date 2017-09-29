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

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Influx Data collector
 */
class InfluxDbCollector implements MetricsCollectorInterface
{
    /**
     * Database client
     *
     * @var \InfluxDB\Database
     */
    private $database;

    /**
     * @param string $connectionDSN
     */
    public function __construct(string $connectionDSN)
    {
        $this->database = Client::fromDSN($connectionDSN);
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
                    $point = new Point($type, $value, $tags, $this->getProcessInfo());

                    $this->database->writePoints([$point], Database::PRECISION_MILLISECONDS);

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
     * Get current process info
     *
     * @return array
     */
    private function getProcessInfo(): array
    {
        static $processInfo;

        if(null === $processInfo)
        {
            $processInfo = [
                'php.gid'           => \getmygid(),
                'php.uid'           => \getmyuid(),
                'php.pid'           => \getmypid(),
                'php.inode'         => \getmyinode(),
                'instance.hostname' => \gethostname()
            ];
        }

        return $processInfo;
    }
}
