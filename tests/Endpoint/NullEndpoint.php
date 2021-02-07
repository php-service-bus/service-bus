<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Endpoint;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Endpoint\DeliveryPackage;
use ServiceBus\Endpoint\Endpoint;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 *
 */
final class NullEndpoint implements Endpoint
{
    public function name(): string
    {
        return __CLASS__;
    }

    public function withNewDeliveryDestination(DeliveryDestination $destination): Endpoint
    {
        return new self();
    }

    public function delivery(DeliveryPackage $package): Promise
    {
        return new Success();
    }

    public function deliveryBulk(array $packages): Promise
    {
        return new Success();
    }
}
