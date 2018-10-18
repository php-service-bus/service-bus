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

namespace Desperado\ServiceBus\Application;

use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Message executor interface
 */
interface MessageExecutor
{
    /**
     * Execute message handlers
     *
     * @param Message                                                          $message
     * @param KernelContext                                                    $context
     * @param array<mixed, \Desperado\ServiceBus\MessageHandlers\Handler> $handlers
     *
     * @return Promise<null>
     */
    public function process(Message $message, KernelContext $context, array $handlers): Promise;
}
