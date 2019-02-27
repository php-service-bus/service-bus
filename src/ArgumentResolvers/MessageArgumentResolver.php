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
 *
 */
final class MessageArgumentResolver implements ArgumentResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(MessageHandlerArgument $argument): bool
    {
        /** The message object MUST be the first argument */
        return 1 === $argument->position;
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function resolve(object $message, ServiceBusContext $context, MessageHandlerArgument $argument): object
    {
        return $message;
    }
}
