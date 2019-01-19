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

use ServiceBus\Common\Messages\Message;
use ServiceBus\Context\KernelContext;
use ServiceBus\MessageHandlers\HandlerArgument;

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
        return $argument->isA(KernelContext::class);
    }

    /**
     * @inheritdoc
     *
     * @return KernelContext
     */
    public function resolve(Message $message, KernelContext $context, HandlerArgument $argument): KernelContext
    {
        return $context;
    }
}
