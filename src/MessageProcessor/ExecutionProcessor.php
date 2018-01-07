<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageProcessor;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ParameterBag;

/**
 * Message execution processor
 */
class ExecutionProcessor
{
    /**
     * Execute message
     *
     * @param AbstractMessage          $message
     * @param ParameterBag             $headers
     * @param AbstractExecutionContext $executionContext
     *
     * @return void
     */
    public function execute(
        AbstractMessage $message,
        ParameterBag $headers,
        AbstractExecutionContext $executionContext
    ): void
    {
        echo __METHOD__ . PHP_EOL;

    }
}
