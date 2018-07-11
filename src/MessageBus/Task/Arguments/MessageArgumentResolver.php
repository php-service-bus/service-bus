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
 *
 */
final class MessageArgumentResolver implements ArgumentResolver
{
    /**
     * @inheritdoc
     */
    public function supports(MessageHandlerArgument $argument): bool
    {
        return $argument->isA(Message::class);
    }

    /**
     * @inheritdoc
     *
     * @return Message
     */
    public function resolve(Message $message, ApplicationContext $applicationContext, MessageHandlerArgument $argument): Message
    {
        return $message;
    }
}
