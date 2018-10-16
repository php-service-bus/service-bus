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
     * @param Message                $message
     * @param MessageDeliveryContext $applicationContext
     * @param HandlerArgument        $argument
     *
     * @return mixed
     */
    public function resolve(Message $message, MessageDeliveryContext $applicationContext, HandlerArgument $argument);
}
