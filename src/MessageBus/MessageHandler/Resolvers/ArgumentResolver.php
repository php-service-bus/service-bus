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
use Desperado\ServiceBus\MessageBus\MessageHandler\Argument;

/**
 * Responsible for resolving the value of an argument
 */
interface ArgumentResolver
{
    /**
     * Whether this resolver can resolve the value for the given Argument
     *
     * @param Argument $argument
     *
     * @return bool
     */
    public function supports(Argument $argument): bool;

    /**
     * Resolve argument value
     *
     * @param Message       $message
     * @param KernelContext $applicationContext
     * @param Argument      $argument
     *
     * @return mixed
     */
    public function resolve(Message $message, KernelContext $applicationContext, Argument $argument);
}
