<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Encoder;

use Desperado\Contracts\Common\Message;

/**
 * Serializing the message object
 */
interface MessageEncoder
{
    /**
     * Encode message to string
     *
     * @param Message $message
     *
     * @return string
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\EncodeMessageFailed
     */
    public function encode(Message $message): string;
}
