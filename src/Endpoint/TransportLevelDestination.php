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

/**
 * Where the message will be sent within the specific transport
 *
 * Marker interface
 * For example @see \Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination
 */
interface TransportLevelDestination
{

}
