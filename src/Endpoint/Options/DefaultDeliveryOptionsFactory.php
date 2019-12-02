<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint\Options;

use ServiceBus\Common\Endpoint\DeliveryOptions;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class DefaultDeliveryOptionsFactory implements DeliveryOptionsFactory
{
    /**
     * @inheritDoc
     */
    public function create($traceId, ?string $messageClass): DeliveryOptions
    {
        $options = DefaultDeliveryOptions::create();
        $options->withTraceId($traceId ?: uuid());

        return $options;
    }
}
