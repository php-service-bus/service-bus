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

namespace Desperado\ServiceBus\Infrastructure\MessageSerialization;

use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Encoding a message into a string
 */
interface MessageEncoder
{
    /**
     * Receive encoder name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Encode message to string
     *
     * @param Message $message
     *
     * @return string
     *
     * @throws \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\EncodeMessageFailed
     */
    public function encode(Message $message): string;

    /**
     * Convert object to array
     *
     * @param object $message
     *
     * @return array<string, mixed>
     *
     * @throws \UnexpectedValueException Unexpected normalize result
     */
    public function normalize(object $message): array;
}
