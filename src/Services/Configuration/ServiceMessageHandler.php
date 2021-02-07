<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Configuration;

use ServiceBus\Common\MessageHandler\MessageHandler;

/**
 *
 */
final class ServiceMessageHandler
{
    private const TYPE_EVENT_LISTENER  = 0;
    private const TYPE_COMMAND_HANDLER = 1;

    /**
     * @var int
     */
    private $type;

    /**
     * @var MessageHandler
     */
    public $messageHandler;

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
