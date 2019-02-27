<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\ArgumentResolvers;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageHandler\MessageHandlerArgument;

/**
 * Responsible for resolving the value of an argument.
 */
interface ArgumentResolver
{
    /**
     * Whether this resolver can resolve the value for the given Argument.
     *
     * @param MessageHandlerArgument $argument
     *
     * @return bool
     */
    public function supports(MessageHandlerArgument $argument): bool;

    /**
     * Resolve argument value.
     *
     * @param object                 $message
     * @param ServiceBusContext      $context
     * @param MessageHandlerArgument $argument
     *
     * @return mixed
     */
    public function resolve(object $message, ServiceBusContext $context, MessageHandlerArgument $argument);
}
