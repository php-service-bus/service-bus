<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\ArgumentResolvers;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Messages\Message;
use ServiceBus\MessageHandlers\HandlerArgument;

/**
 * Responsible for resolving the value of an argument
 */
interface ArgumentResolver
{
    /**
     * Whether this resolver can resolve the value for the given Argument
     *
     * @param HandlerArgument $argument
     *
     * @return bool
     */
    public function supports(HandlerArgument $argument): bool;

    /**
     * Resolve argument value
     *
     * @param Message           $message
     * @param ServiceBusContext $context
     * @param HandlerArgument   $argument
     *
     * @return mixed
     */
    public function resolve(Message $message, ServiceBusContext $context, HandlerArgument $argument);
}
