<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Task\Arguments;

use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Kernel\ApplicationContext;
use Desperado\ServiceBus\MessageBus\Configuration\MessageHandlerArgument;

/**
 * Responsible for resolving the value of an argument
 */
interface ArgumentResolver
{
    /**
     * Whether this resolver can resolve the value for the given Argument
     *
     * @param MessageHandlerArgument $argument
     *
     * @return bool
     */
    public function supports(MessageHandlerArgument $argument): bool;

    /**
     * Resolve argument
     *
     * @param Message                $message
     * @param ApplicationContext     $applicationContext
     * @param MessageHandlerArgument $argument
     *
     * @return mixed
     */
    public function resolve(Message $message, ApplicationContext $applicationContext, MessageHandlerArgument $argument);
}
