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

namespace Desperado\ServiceBus\ArgumentResolvers;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\MessageHandlers\HandlerArgument;

/**
 *
 */
final class ContextArgumentResolver implements ArgumentResolver
{
    /**
     * @inheritdoc
     */
    public function supports(HandlerArgument $argument): bool
    {
        return $argument->isA(MessageDeliveryContext::class);
    }

    /**
     * @inheritdoc
     *
     * @return MessageDeliveryContext
     */
    public function resolve(Message $message, MessageDeliveryContext $context, HandlerArgument $argument): MessageDeliveryContext
    {
        return $context;
    }
}
