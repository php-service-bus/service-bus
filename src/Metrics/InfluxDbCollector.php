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
    private const DEFAULT_BULK_SIZE = 20;

    /**
     * Database client
     *
     * @var \InfluxDB\Database
     */
    private $database;

    /**
     * Local points storage
     *
     * @var Point[]
     */
    private $points = [];

    /**
     * Number of entries required to send to the database
     *
     * @var int
     */
    private $bulkSize;

    /**
     * @param string $connectionDSN
     * @param int    $bulkSize
     */
    public function __construct(string $connectionDSN, int $bulkSize = self::DEFAULT_BULK_SIZE)
    {
        $this->database = Client::fromDSN($connectionDSN);
        $this->bulkSize = $bulkSize;
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
                    $this->points[] = new Point($type, $value, $tags, $this->getProcessInfo());

                    if($this->bulkSize <= \count($this->points))
                    {
                        $this->database->writePoints($this->points, Database::PRECISION_MILLISECONDS);

                        $this->points = [];
                    }

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
