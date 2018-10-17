<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Endpoint;

use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;

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
     * @param Message         $message
     * @param DeliveryOptions $options
     *
     * @return Promise<null>
     */
    public function delivery(Message $message, DeliveryOptions $options): Promise;
}
