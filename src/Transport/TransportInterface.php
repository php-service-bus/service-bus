<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport;

use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\ServiceBus\Transport\Message\Message;
use React\Promise\PromiseInterface;

/**
 * Message transport interface
 */
interface TransportInterface
{
    /**
     * Subscribe to messages
     *
     * @param string   $entryPointName
     * @param callable $messageHandler function(IncomingMessageContainer $incomingMessageContainer) {}
     *
     * @return void
     *
     * @throws \Exception
     */
    public function listen(string $entryPointName, callable $messageHandler): void;

    /**
     * Unsubscribe from messages
     *
     * @return PromiseInterface
     *
     * @throws \Exception
     */
    public function disconnect(): PromiseInterface;

    /**
     * Send a message
     *
     * @param Message $message
     *
     * @return PromiseInterface
     *
     * @throws \Exception
     */
    public function send(Message $message): PromiseInterface;

    /**
     * Get message serializer
     *
     * @return MessageSerializerInterface
     */
    public function getMessageSerializer(): MessageSerializerInterface;
}
