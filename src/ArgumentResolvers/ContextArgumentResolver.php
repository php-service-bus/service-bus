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
final class ContextArgumentResolver implements ArgumentResolver
{
    public function supports(MessageHandlerArgument $argument): bool
    {
        return $argument->isA(ServiceBusContext::class);
    }

    public function resolve(
        object $message,
        ServiceBusContext $context,
        MessageHandlerArgument $argument
    ): ServiceBusContext {
        return $context;
    }
}
