<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Configuration;

use ServiceBus\Common\MessageHandler\MessageHandler;

/**
 *
 */
final class ServiceMessageHandler
{
    private const TYPE_EVENT_LISTENER  = 0;
    private const TYPE_COMMAND_HANDLER = 1;

    private int           $type;
    public MessageHandler $messageHandler;

    public static function createCommandHandler(MessageHandler $messageHandler): self
    {
        return new self(self::TYPE_COMMAND_HANDLER, $messageHandler);
    }

    public static function createEventListener(MessageHandler $messageHandler): self
    {
        return new self(self::TYPE_EVENT_LISTENER, $messageHandler);
    }

    public function isCommandHandler(): bool
    {
        return self::TYPE_COMMAND_HANDLER === $this->type;
    }

    private function __construct(int $type, MessageHandler $messageHandler)
    {
        $this->type           = $type;
        $this->messageHandler = $messageHandler;
    }
}
