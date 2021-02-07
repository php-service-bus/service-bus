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
 * Responsible for resolving the value of an argument.
 */
interface ArgumentResolver
{
    /**
     * Whether this resolver can resolve the value for the given argument.
     */
    public function supports(MessageHandlerArgument $argument): bool;

    /**
     * Resolve argument value.
     */
    public function resolve(object $message, ServiceBusContext $context, MessageHandlerArgument $argument): mixed;
}
