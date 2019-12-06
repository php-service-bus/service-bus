<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Watchers;

use function ServiceBus\Common\formatBytes;
use Amp\Loop;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Periodic forced launch of the garbage collector.
 *
 * @codeCoverageIgnore
 */
final class GarbageCollectorWatcher
{
    /** @var int milliseconds */
    private const DEFAULT_INTERVAL = 600000;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $interval;

    /** @var string|null */
    private $watcherId = null;

    /**
     * @param int $interval delay in milliseconds
     */
    public function __construct(int $interval = self::DEFAULT_INTERVAL, ?LoggerInterface $logger = null)
    {
        $this->interval = $interval;
        $this->logger   = $logger ?? new NullLogger();
    }

    public function __destruct()
    {
        if (null !== $this->watcherId)
        {
            Loop::cancel($this->watcherId);

            $this->watcherId = null;
        }
    }

    public function run(): void
    {
        $logger = $this->logger;

        $this->watcherId = Loop::repeat(
            $this->interval,
            static function () use ($logger): void
            {
                $logger->info('Forces collection of any existing garbage cycles', ['number' => \gc_collect_cycles()]);
                $logger->info(
                    'Reclaims memory used by the Zend Engine memory manager',
                    ['bytes' => formatBytes(\gc_mem_caches())]
                );
            }
        );

        Loop::unreference($this->watcherId);
    }
}
