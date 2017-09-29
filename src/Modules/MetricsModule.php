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

namespace Desperado\Framework\Modules;

use Desperado\CQRS\Behaviors\MetricsBehavior;
use Desperado\CQRS\MessageBusBuilder;
use Desperado\CQRS\Metrics\MetricsCollectorInterface;

/**
 * Collect metrics
 */
class MetricsModule implements ModuleInterface
{
    /**
     * Metrics collector
     *
     * @var MetricsCollectorInterface
     */
    private $collector;

    /**
     * @param MetricsCollectorInterface $collector
     */
    public function __construct(MetricsCollectorInterface $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void
    {
        $messageBusBuilder->pushBehavior(
            new MetricsBehavior($this->collector)
        );
    }
}
