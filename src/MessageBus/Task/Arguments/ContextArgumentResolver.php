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
final class ContextArgumentResolver implements ArgumentResolver
{
    /**
     * @inheritdoc
     */
    public function supports(MessageHandlerArgument $argument): bool
    {
        return $argument->isA(ApplicationContext::class);
    }

    /**
     * @inheritdoc
     *
     * @return ApplicationContext
     */
    public function resolve(
        Message $message,
        ApplicationContext $applicationContext,
        MessageHandlerArgument $argument
    ): ApplicationContext
    {
        return $applicationContext;
    }
}
