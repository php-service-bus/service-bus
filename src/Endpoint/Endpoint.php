<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use Amp\Promise;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Common\Messages\Message;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Destination when sending a message
 */
interface Endpoint
{
    /**
     * Receive endpoint name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Create a new endpoint object with the same transport but different delivery routes
     *
     * @param DeliveryDestination $destination
     *
     * @return MessageDeliveryEndpoint
     */
    public function withNewDeliveryDestination(DeliveryDestination $destination): Endpoint;

    /**
     * Send message to endpoint
     *
     * @param Message                $message
     * @param DeliveryOptions $options
     *
     * @return Promise It does not return any result
     *
     * @throws \ServiceBus\MessageSerializer\Exceptions\EncodeMessageFailed
     * @throws \ServiceBus\Transport\Common\Exceptions\SendMessageFailed Failed to send message
     */
    public function delivery(Message $message, DeliveryOptions $options): Promise;
}
