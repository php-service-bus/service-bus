<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Transport\Context;

use Desperado\ServiceBus\Transport\Message\Message;

/**
 * The context of the incoming message
 */
interface IncomingMessageContextInterface
{
    /**
     * Get received message
     *
     * @return Message
     */
    public function getReceivedMessage(): Message;
}
