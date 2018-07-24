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

/**
 *
 */
interface MessageDeliveryContext
{
    public function delivery(Message ...$messages): Promise;
}
