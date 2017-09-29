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

use React\Promise\PromiseInterface;

/**
 * Collector backend
 */
interface MetricsCollectorInterface
{
    public const TYPE_HANDLE_MEMORY_USAGE = 'handleMemoryUsage';
    public const TYPE_HANDLE_WORK_TIME = 'handleWorkTime';

    public const TYPE_FLUSH_MEMORY_USAGE = 'flushMemoryUsage';
    public const TYPE_FLUSH_WORK_TIME = 'flushWorkTime';

    /**
     * Push metric data
     *
     * @param string $type
     * @param mixed  $value
     * @param array  $tags
     *
     * @return PromiseInterface
     */
    public function push(string $type, $value, array $tags = []): PromiseInterface;
}
