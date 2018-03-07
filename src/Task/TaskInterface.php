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
use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\ServiceBus\Services\Handlers\AbstractMessageExecutionParameters;
use React\Promise\PromiseInterface;

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
     * @param ExecutionContextInterface $context
     * @param array                    $additionalArguments
     *
     * @return PromiseInterface|null
     */
    public function __invoke(
        AbstractMessage $message,
        ExecutionContextInterface $context,
        array $additionalArguments = []
    ): ?PromiseInterface;
}
