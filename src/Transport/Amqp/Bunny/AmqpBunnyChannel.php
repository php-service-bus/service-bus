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

namespace Desperado\ServiceBus\Transport\Amqp\Bunny;

use function Amp\call;
use Amp\Promise;
use Amp\Success;
use Bunny\Channel;
use Bunny\ChannelMethods;
use Bunny\ChannelModeEnum;
use Bunny\Exception\ChannelException;

/**
 *
 */
final class AmqpBunnyChannel extends Channel
{
    use ChannelMethods
    {
        ChannelMethods::consume as private consumeExtImpl;
    }


    public function consume(callable $callback, $queue = "", $consumerTag = "", $noLocal = false, $noAck = false, $exclusive = false, $nowait = false, $arguments = [])
    {
        return call(
            function() use ($callback, $queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments)
            {
                $response = yield $this->consumeExtImpl($queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments);

                $this->deliverCallbacks[$response->consumerTag] = $callback;

                return yield new Success($response);
            }
        );
    }

    public function confirmSelect(callable $callback = null, $nowait = false)
    {
        return call(
            function($callback)
            {
                if($this->mode !== ChannelModeEnum::REGULAR)
                {
                    throw new ChannelException("Channel not in regular mode, cannot change to transactional mode.");
                }

                $response = yield $this->confirmSelectImpl(false);

                $this->mode = ChannelModeEnum::CONFIRM;
                $this->deliveryTag = 0;

                if($callback)
                {
                    $this->addAckListener($callback);
                }

                return yield new Success($response);
            }
        );
    }
}
