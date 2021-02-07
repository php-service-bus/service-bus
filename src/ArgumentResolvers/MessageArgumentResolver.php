<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\ArgumentResolvers;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageHandler\MessageHandlerArgument;

/**
 *
 */
final class MessageArgumentResolver implements ArgumentResolver
{
    public function supports(MessageHandlerArgument $argument): bool
    {
        /** The message object MUST be the first argument */
        return $argument->position === 1;
    }

    public function resolve(object $message, ServiceBusContext $context, MessageHandlerArgument $argument): object
    {
        return $message;
    }
}
