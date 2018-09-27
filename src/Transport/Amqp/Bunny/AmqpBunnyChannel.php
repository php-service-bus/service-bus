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
use Amp\Coroutine;
use Amp\Promise;
use Amp\Success;
use Bunny\Channel;
use Bunny\ChannelModeEnum;
use Bunny\ChannelStateEnum;
use Bunny\Constants;
use Bunny\Exception\ChannelException;
use Bunny\Message;
use Bunny\Protocol as AmqpProtocol;

/**
 * The library (jakubkulhan/bunny) architecture does not allow to expand its functionality correctly
 *
 * @todo: Prepare a pull request including fixes
 *
 * @method AmqpBunnyClient getClient()
 */
final class AmqpBunnyChannel extends Channel
{
    /**
     * @psalm-suppress MissingParamType
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @param callable $callback
     * @param string   $queue
     * @param string   $consumerTag
     * @param bool     $noLocal
     * @param bool     $noAck
     * @param bool     $exclusive
     * @param bool     $nowait
     * @param array    $arguments
     *
     * @return Promise<\Bunny\Protocol\MethodBasicConsumeOkFrame>
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
                /** @var \Bunny\Protocol\MethodBasicConsumeOkFrame $response */
                $response = yield $this->getClient()->consume(
                    $this->getChannelId(), $queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments
                );

                $this->deliverCallbacks[$response->consumerTag] = $callback;

                return yield new Success($response);
            },
            $callback, $queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments
        );
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return Promise<bool>
     */
    public function publish(
        $body,
        array $headers = [],
        $exchange = '',
        $routingKey = '',
        $mandatory = false,
        $immediate = false
    ): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(string $body, array $headers, string $exchange, string $routingKey, bool $mandatory, bool $immediate): \Generator
            {
                return yield new Success(
                    yield $this->getClient()->publish(
                        $this->getChannelId(), $body, $headers, $exchange, $routingKey, $mandatory, $immediate
                    )
                );
            },
            $body, $headers, $exchange, $routingKey, $mandatory, $immediate
        );
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @inheritdoc
     *
     * @return Promise<\Bunny\Protocol\MethodConfirmSelectOkFrame>
     */
    public function confirmSelect(callable $callback = null, $nowait = false): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(?callable $callback, bool $nowait): \Generator
            {
                if($this->mode !== ChannelModeEnum::REGULAR)
                {
                    throw new ChannelException(
                        'Channel not in regular mode, cannot change to transactional model'
                    );
                }

                $response = yield $this->getClient()->confirmSelect($this->getChannelId(), $nowait);

                $this->mode        = ChannelModeEnum::CONFIRM;
                $this->deliveryTag = 0;

                if(null !== $callback)
                {
                    $this->addAckListener($callback);
                }

                return yield new Success($response);
            },
            $callback, $nowait
        );
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return Promise<\Bunny\Message|bool>
     */
    public function get($queue = '', $noAck = false): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(string $queue, bool $noAck): \Generator
            {
                /** @var AmqpProtocol\AbstractFrame $response */
                $response = yield $this->getClient()->get($this->getChannelId(), $queue, $noAck);

                if($response instanceof AmqpProtocol\MethodBasicGetEmptyFrame)
                {
                    return yield new Success();
                }

                if($response instanceof AmqpProtocol\MethodBasicGetOkFrame)
                {
                    return yield new Coroutine($this->getMessage($response));
                }

                throw new \LogicException('This statement should never be reached.');
            },
            $queue, $noAck
        );
    }

    /**
     * @param AmqpProtocol\MethodBasicGetOkFrame $frame
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @psalm-suppress InvalidReturnType
     *
     * @return \Generator<\Bunny\Message|bool>
     */
    private function getMessage(AmqpProtocol\MethodBasicGetOkFrame $frame): \Generator
    {
        $this->state = ChannelStateEnum::AWAITING_HEADER;

        /** @var AmqpProtocol\ContentHeaderFrame $headerFrame */
        $headerFrame = yield $this->getClient()->awaitContentHeader($this->getChannelId());

        $this->headerFrame       = $headerFrame;
        $this->bodySizeRemaining = $headerFrame->bodySize;
        $this->state             = ChannelStateEnum::AWAITING_BODY;

        while($this->bodySizeRemaining > 0)
        {
            /** @var AmqpProtocol\ContentBodyFrame $bodyFrame */
            $bodyFrame = yield $this->getClient()->awaitContentBody($this->getChannelId());

            $this->bodyBuffer->append($bodyFrame->payload);
            $this->bodySizeRemaining -= $bodyFrame->payloadSize;

            if(0 > $this->bodySizeRemaining)
            {
                $this->state = ChannelStateEnum::ERROR;

                $errorMessage = \sprintf('Body overflow, received %s more bytes.', -$this->bodySizeRemaining);

                yield $this->client->disconnect(
                    Constants::STATUS_SYNTAX_ERROR,
                    $errorMessage

                );

                throw new ChannelException($errorMessage);
            }
        }

        $this->state = ChannelStateEnum::READY;

        $message = new Message(
            '',
            (string) $frame->deliveryTag,
            $frame->redelivered,
            $frame->exchange,
            $frame->routingKey,
            $this->headerFrame->toArray(),
            $this->bodyBuffer->consume($this->bodyBuffer->getLength())
        );

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->headerFrame = null;

        return $message;
    }


    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    protected function onBodyComplete(): void
    {
        /** @psalm-suppress  RedundantConditionGivenDocblockType */
        if(null !== $this->returnFrame)
        {
            $this->processReturnFrame();

            return;
        }

        /** @psalm-suppress  RedundantConditionGivenDocblockType */
        if(null !== $this->deliverFrame)
        {
            $this->processDeliverFrame();

            return;
        }

        /** @psalm-suppress  RedundantConditionGivenDocblockType */
        if(null !== $this->getOkFrame)
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
            '',
            '',
            false,
            $this->returnFrame->exchange,
            $this->returnFrame->routingKey,
            $this->headerFrame->toArray(),
            $content
        );

        foreach($this->returnCallbacks as $callback)
        {
            /** @psalm-suppress InvalidArgument */
            asyncCall($callback, $message, $this->returnFrame);
        }

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->returnFrame = null;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
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
                (string) $this->deliverFrame->deliveryTag,
                $this->deliverFrame->redelivered,
                $this->deliverFrame->exchange,
                $this->deliverFrame->routingKey,
                $this->headerFrame->toArray(),
                $content
            );

            $callback = $this->deliverCallbacks[$this->deliverFrame->consumerTag];

            /** @psalm-suppress InvalidArgument */
            asyncCall($callback, $message, $this, $this->client);
        }

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->deliverFrame = null;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->headerFrame = null;
    }

    /**
     * @return void
     */
    private function processGetOkFrame(): void
    {
        $content = $this->bodyBuffer->consume($this->bodyBuffer->getLength());

        /** Deferred has to be first nullified and then resolved, otherwise results in race condition */
        $deferred = $this->getDeferred;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->getDeferred = null;

        $deferred->resolve(new Message(
            '',
            (string) $this->getOkFrame->deliveryTag,
            $this->getOkFrame->redelivered,
            $this->getOkFrame->exchange,
            $this->getOkFrame->routingKey,
            $this->headerFrame->toArray(),
            $content
        ));

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->getOkFrame = null;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->headerFrame = null;
    }
}
