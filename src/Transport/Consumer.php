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

namespace Desperado\ServiceBus\Transport;

/**
 * Subscriber to new messages from the broker
 */
interface Consumer
{
    /**
     * Waiting for new messages from the broker
     *
     * @param callable $messageProcessor static function (IncomingEnvelope $incomingEnvelope): void {}
     *
     * @return void
     */
    public function listen(callable $messageProcessor): void;
}
