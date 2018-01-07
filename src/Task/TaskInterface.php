<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Services\Handlers\Messages\AbstractMessageExecutionParameters;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;

/**
 *  The task to be executed
 */
interface TaskInterface
{
    /**
     * Get message-specific options
     *
     * @return AbstractMessageExecutionParameters
     */
    public function getOptions(): AbstractMessageExecutionParameters;

    /**
     * Execute task
     *
     * @param AbstractMessage          $message
     * @param AbstractExecutionContext $context
     *
     * @return Task|null
     */
    public function __invoke(AbstractMessage $message, AbstractExecutionContext $context): ?TaskInterface;
}
