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

namespace Desperado\ServiceBus\Common\ExecutionContext;

use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Endpoint\DeliveryOptions;

/**
 *
 */
interface MessageDeliveryContext
{
    /**
     * Execute simple messages (commands\events) delivery
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param Message              $message
     * @param DeliveryOptions|null $options
     *
     * @return Promise It does not return any result
     */
    public function delivery(Message $message, ?DeliveryOptions $options = null): Promise;
}
