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

use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Fake collector
 */
class NullMetricsCollector implements MetricsCollectorInterface
{
    /**
     * @inheritdoc
     */
    public function push(string $type, $value, array $tags = []): PromiseInterface
    {
        return new Promise(
            function($resolve)
            {
                $resolve();
            }
        );
    }
}
