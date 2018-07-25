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

namespace Desperado\ServiceBus\Common\Contract\Messages;

/**
 * Used to request that an action should be taken. A Command is intended to be sent to a receiver (all commands should
 * have one logical owner and should be sent to the endpoint responsible for processing)
 */
interface Command extends Message
{

}
