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

use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use Amp\Success;
use Bunny\Channel;
use Bunny\ChannelMethods;
use Bunny\ChannelModeEnum;
use Bunny\Exception\ChannelException;
use Bunny\Message;

/**
 * The library (jakubkulhan/bunny) architecture does not allow to expand its functionality correctly
 *
 * @todo: Prepare a pull request including fixes
 */
final class AmqpBunnyChannel extends Channel
{
    use ChannelMethods
    {
        ChannelMethods::consume as private consumeExtImpl;
    }

    /**
     * @param callable $callback
     * @param string   $queue
     * @param string   $consumerTag
     * @param bool     $noLocal
     * @param bool     $noAck
     * @param bool     $exclusive
     * @param bool     $nowait
     * @param array    $arguments
     *
     * @return Promise<\Bunny\Protocol\MethodConfirmSelectOkFrame>
     */
    public function consume(
        callable $callback,
        $queue = '',
        $consumerTag = '',
        $noLocal = false,
        $noAck = false,
        $exclusive = false,
        $nowait = false,
        $arguments = []
    ): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(
                callable $callback, string $queue, string $consumerTag, bool $noLocal, bool $noAck,
                bool $exclusive, bool $nowait, array $arguments
            ): \Generator
            {
                $response = yield $this->consumeExtImpl($queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments);

                $this->deliverCallbacks[$response->consumerTag] = $callback;

                return yield new Success($response);
            },
            $callback, $queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments
        );
    }

    /**
     * @inheritdoc
     *
     * @return Promise<\Bunny\Protocol\MethodConfirmSelectOkFrame>
     */
    public function confirmSelect(callable $callback = null, $nowait = false): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(?callable $callback): \Generator
            {
                if($this->mode !== ChannelModeEnum::REGULAR)
                {
                    throw new ChannelException(
                        'Channel not in regular mode, cannot change to transactional model'
                    );
                }

                $response = yield $this->confirmSelectImpl(false);

                $this->mode        = ChannelModeEnum::CONFIRM;
                $this->deliveryTag = 0;

                if(null !== $callback)
                {
                    $this->addAckListener($callback);
                }

                return yield new Success($response);
            },
            $callback
        );
    }

    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    protected function onBodyComplete(): void
    {
        if(null !== $this->returnFrame)
        {
            $this->processReturnFrame();

            return;
        }

        if($this->deliverFrame)
        {
            $this->processDeliverFrame();

            return;
        }

        if($this->getOkFrame)
        {
            $this->processGetOkFrame();

            return;
        }

        throw new \LogicException(
            'Either return or deliver frame has to be handled here'
        );
    }

    /**
     * @return void
     */
    private function processReturnFrame(): void
    {
        $content = $this->bodyBuffer->consume($this->bodyBuffer->getLength());
        $message = new Message(
            null,
            null,
            false,
            $this->returnFrame->exchange,
            $this->returnFrame->routingKey,
            $this->headerFrame->toArray(),
            $content
        );

        foreach($this->returnCallbacks as $callback)
        {
            asyncCall($callback, $message, $this->returnFrame);
        }

        $this->returnFrame = null;
        $this->headerFrame = null;
    }

    /**
     * @return void
     */
    private function processDeliverFrame(): void
    {
        $content = $this->bodyBuffer->consume($this->bodyBuffer->getLength());

        if(true === isset($this->deliverCallbacks[$this->deliverFrame->consumerTag]))
        {
            $message = new Message(
                $this->deliverFrame->consumerTag,
                $this->deliverFrame->deliveryTag,
                $this->deliverFrame->redelivered,
                $this->deliverFrame->exchange,
                $this->deliverFrame->routingKey,
                $this->headerFrame->toArray(),
                $content
            );

            $callback = $this->deliverCallbacks[$this->deliverFrame->consumerTag];

            asyncCall($callback, $message, $this, $this->client);
        }

        $this->deliverFrame = null;
        $this->headerFrame  = null;
    }

    /**
     * @return void
     */
    private function processGetOkFrame(): void
    {
        $content = $this->bodyBuffer->consume($this->bodyBuffer->getLength());

        /** Deferred has to be first nullified and then resolved, otherwise results in race condition */
        $deferred          = $this->getDeferred;
        $this->getDeferred = null;

        $deferred->resolve(new Message(
            null,
            $this->getOkFrame->deliveryTag,
            $this->getOkFrame->redelivered,
            $this->getOkFrame->exchange,
            $this->getOkFrame->routingKey,
            $this->headerFrame->toArray(),
            $content
        ));

        $this->getOkFrame  = null;
        $this->headerFrame = null;
    }
}
