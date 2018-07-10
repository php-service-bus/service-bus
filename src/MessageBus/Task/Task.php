<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Task;

use Amp\Promise;
use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Kernel\ApplicationContext;

/**
 *
 */
interface Task
{
    /**
     * Execute handler
     *
     * @param Message            $message
     * @param ApplicationContext $context
     *
     * @return Promise
     */
    public function __invoke(Message $message, ApplicationContext $context): Promise;
}