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

namespace Desperado\ServiceBus\Tests\EventSourcing\Stubs;

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;

/**
 *
 */
final class TestAggregateContext implements MessageDeliveryContext
{
    public $messages = [];

    /**
     * @inheritdoc
     */
    public function delivery(Message ...$messages): Promise
    {
        $this->messages = $messages;

        return new Success();
    }
}
