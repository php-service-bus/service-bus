<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Services\Configuration;

use ServiceBus\Common\MessageHandler\MessageHandler;

final class ServiceMessageHandler
{
    /**
     * @psalm-readonly
     *
     * @var ServiceMessageHandlerType
     */
    public $type;

    /**
     * @psalm-readonly
     *
     * @var MessageHandler
     */
    public $messageHandler;

    public static function createCommandHandler(MessageHandler $messageHandler): self
    {
        return new self(ServiceMessageHandlerType::COMMAND_HANDLER, $messageHandler);
    }

    public static function createEventListener(MessageHandler $messageHandler): self
    {
        return new self(ServiceMessageHandlerType::EVENT_LISTENER, $messageHandler);
    }

    private function __construct(ServiceMessageHandlerType $type, MessageHandler $messageHandler)
    {
        $this->type           = $type;
        $this->messageHandler = $messageHandler;
    }
}
