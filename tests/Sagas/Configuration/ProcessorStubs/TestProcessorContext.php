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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;

/**
 *
 */
final class TestProcessorContext implements MessageDeliveryContext
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * @inheritdoc
     */
    public function delivery(Message ...$messages): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(array $messages): void
            {

                $this->messages = \array_merge($messages, $this->messages);
            },
            $messages
        );
    }
}
