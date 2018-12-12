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

namespace Desperado\ServiceBus\Tests\Stubs\Transport;

use Amp\Success;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Endpoint\DeliveryOptions;
use Desperado\ServiceBus\Endpoint\Endpoint;

/**
 *
 */
final class VirtualTransportEndpoint implements Endpoint
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'testing';
    }

    /**
     * @inheritDoc
     */
    public function delivery(Message $message, DeliveryOptions $options): Promise
    {
        return new Success($message);
    }
}
