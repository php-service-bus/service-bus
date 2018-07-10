<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Decoder;

use Desperado\Contracts\Common\Message;

/**
 * Restore the message object
 */
interface MessageDecoder
{
    /**
     * Restore message from string
     *
     * @param string $serializedMessage
     *
     * @return Message
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\DecodeMessageFailed
     */
    public function decode(string $serializedMessage): Message;

    /**
     * Unserialize received content
     *
     * @param string $serializedMessage
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\DecodeMessageFailed
     */
    public function unserialize(string $serializedMessage): array;

    /**
     * Denormalize message data
     *
     * @param string $messageClass
     * @param array  $payload
     *
     * @return Message
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\DecodeMessageFailed
     */
    public function denormalize(string $messageClass, array $payload): Message;
}
