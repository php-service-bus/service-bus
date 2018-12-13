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
 * Decode a message string into an object
 */
interface MessageDecoder
{
    /**
     * Receive decoder name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Restore message from string
     *
     * @param string $serializedMessage
     *
     * @return Message
     *
     * @throws \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     */
    public function decode(string $serializedMessage): Message;

    /**
     * Convert array to specified object
     *
     * @param array<string, mixed> $payload
     * @param string               $class
     *
     * @return object
     */
    public function denormalize(array $payload, string $class): object;
}
