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
     * @param array    $clients
     *
     * @return void
     */
    public function listen(string $entryPointName, callable $messageHandler, array $clients = []): void;

    /**
     * Unsubscribe from messages
     *
     * @return PromiseInterface
     */
    public function disconnect(): PromiseInterface;

    /**
     * Send a message
     *
     * @param Message $message
     *
     * @return PromiseInterface
     */
    public function send(Message $message): PromiseInterface;

    /**
     * Get message serializer
     *
     * @return MessageSerializerInterface
     */
    public function getMessageSerializer(): MessageSerializerInterface;
}
