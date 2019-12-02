<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use Amp\Promise;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Destination when sending a message.
 */
interface Endpoint
{
    /**
     * Receive endpoint name.
     */
    public function name(): string;

    /**
     * Create a new endpoint object with the same transport but different delivery routes.
     */
    public function withNewDeliveryDestination(DeliveryDestination $destination): Endpoint;

    /**
     * Send message to endpoint.
     *
     * @throws \ServiceBus\MessageSerializer\Exceptions\EncodeMessageFailed
     */
    public function delivery(object $message, DeliveryOptions $options): Promise;
}
