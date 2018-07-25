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

namespace Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\MessageBus\MessageHandler\HandlerArgument;

/**
 *
 */
final class MessageArgumentResolver implements ArgumentResolver
{
    /**
     * @inheritdoc
     */
    public function supports(HandlerArgument $argument): bool
    {
        return $argument->isA(Message::class);
    }

    /**
     * @inheritdoc
     *
     * @return Message
     */
    public function resolve(Message $message, KernelContext $context, HandlerArgument $argument): Message
    {
        return $message;
    }
}
