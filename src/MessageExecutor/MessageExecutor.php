<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\MessageExecutor;

use Amp\Promise;
use ServiceBus\Context\KernelContext;
use ServiceBus\Common\Messages\Message;

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
