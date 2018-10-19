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

namespace Desperado\ServiceBus\MessageExecutor;

use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 *
 */
interface MessageExecutor
{
    /**
     * Handle message
     *
     * @param Message       $message
     * @param KernelContext $context
     *
     * @return Promise It does not return any result
     *
     * @throws \Throwable
     */
    public function __invoke(Message $message, KernelContext $context): Promise;
}
