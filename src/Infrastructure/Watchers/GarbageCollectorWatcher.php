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

namespace Desperado\ServiceBus\Infrastructure\Watchers;

use Amp\Loop;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Periodic forced launch of the garbage collector
 */
final class GarbageCollectorWatcher
{
    /** @var int milliseconds */
    private const DEFAULT_INTERVAL = 600000;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $interval;

    /**
     * @var string|null
     */
    private $watcherId;

    /**
     * @param int                  $interval delay in milliseconds
     * @param LoggerInterface|null $logger
     */
    public function __construct(int $interval = self::DEFAULT_INTERVAL, ?LoggerInterface $logger = null)
    {
        $this->interval = $interval;
        $this->logger   = $logger ?? new NullLogger();
    }

    public function __destruct()
    {
        if(null !== $this->watcherId)
        {
            Loop::cancel($this->watcherId);

            $this->watcherId = null;
        }
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $logger = $this->logger;

        $this->watcherId = Loop::repeat(
            $this->interval,
            static function() use ($logger): void
            {
                $logger->info('Forces collection of any existing garbage cycles', ['number' => \gc_collect_cycles()]);
                $logger->info(
                    'Reclaims memory used by the Zend Engine memory manager',
                    ['bytes' => self::formatBytes(\gc_mem_caches())]
                );
            }
        );

        Loop::unreference($this->watcherId);
    }

    /**
     * Formats bytes into a human readable string
     *
     * @param int $bytes
     *
     * @return string
     */
    private static function formatBytes(int $bytes): string
    {
        if(1024 * 1024 < $bytes)
        {
            return \round($bytes / 1024 / 1024, 2) . ' mb';
        }

        if(1024 < $bytes)
        {
            return \round($bytes / 1024, 2) . ' kb';
        }

        return $bytes . ' b';
    }
}
