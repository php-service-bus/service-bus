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
use ServiceBus\Common\Messages\Message;

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
     * Send message to endpoint
     *
     * @param Message                $message
     * @param DefaultDeliveryOptions $options
     *
     * @return Promise It does not return any result
     *
     * @throws \ServiceBus\MessageSerializer\Exceptions\EncodeMessageFailed
     * @throws \ServiceBus\Transport\Common\Exceptions\SendMessageFailed Failed to send message
     */
    public function delivery(Message $message, DefaultDeliveryOptions $options): Promise;
}
