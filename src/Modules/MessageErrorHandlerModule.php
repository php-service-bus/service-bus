<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Modules;

use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\Task\Behaviors\ErrorHandleBehavior;

/**
 * Apply service error handlers support
 */
class MessageErrorHandlerModule implements ModuleInterface
{
    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void
    {
        $messageBusBuilder->pushBehavior(
            ErrorHandleBehavior::create(
                Handlers\Exceptions\ExceptionHandlersCollection::create()
            )
        );
    }
}
