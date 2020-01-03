<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Endpoint;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Endpoint\Endpoint;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 *
 */
final class NullEndpoint implements Endpoint
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return __CLASS__;
    }

    /**
     * @inheritDoc
     */
    public function withNewDeliveryDestination(DeliveryDestination $destination): Endpoint
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function delivery(object $message, DeliveryOptions $options): Promise
    {
        return new Success();
    }
}
