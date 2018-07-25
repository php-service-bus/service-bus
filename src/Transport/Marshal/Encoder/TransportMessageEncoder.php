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

namespace Desperado\ServiceBus\Transport\Marshal\Encoder;

use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Serializing the message object
 */
interface TransportMessageEncoder
{
    /**
     * Encode message to string
     *
     * @param Message $message
     *
     * @return string
     *
     * @throws \Desperado\ServiceBus\Transport\Marshal\Exceptions\EncodeMessageFailed
     */
    public function encode(Message $message): string;

}