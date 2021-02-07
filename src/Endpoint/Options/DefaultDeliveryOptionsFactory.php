<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Endpoint\Options;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 *
 */
final class DefaultDeliveryOptionsFactory implements DeliveryOptionsFactory
{
    public function create(?string $messageClass = null): DeliveryOptions
    {
        return DefaultDeliveryOptions::create();
    }
}
