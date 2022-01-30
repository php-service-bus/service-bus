<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Endpoint;

use Amp\Promise;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Destination when sending a message.
 */
interface Endpoint
{
    /**
     * Receive endpoint name.
     *
     * @psalm-return non-empty-string
     */
    public function name(): string;

    /**
     * Create a new endpoint object with the same transport but different delivery routes.
     */
    public function withNewDeliveryDestination(DeliveryDestination $destination): Endpoint;

    /**
     * Send message to endpoint.
     *
     * @throws \ServiceBus\MessageSerializer\Exceptions\EncodeObjectFailed
     */
    public function delivery(DeliveryPackage $package): Promise;

    /**
     * Send messages to endpoint.
     *
     * @psalm-param list<DeliveryPackage> $packages
     *
     * @throws \ServiceBus\MessageSerializer\Exceptions\EncodeObjectFailed
     */
    public function deliveryBulk(array $packages): Promise;
}
