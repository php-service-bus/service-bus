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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\BunnyRabbitMQ;

use function Amp\asyncCall;
use function Amp\call;
use Amp\Coroutine;
use Amp\Promise;
use Bunny\Channel;
use Bunny\Message;

/**
 * The library (jakubkulhan/bunny) architecture does not allow to expand its functionality correctly
 *
 * @todo: Prepare a pull request including fixes
 *
 * @method BunnyClientOverride getClient()
 */
final class BunnyChannelOverride extends Channel
{
    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the promise
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param callable(BunnyEnvelope, BunnyChannelOverride):void $callback
     * @param string $queue
     * @param string $consumerTag
     * @param bool   $noLocal
     * @param bool   $noAck
     * @param bool   $exclusive
     * @param bool   $nowait
     * @param array  $arguments
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
                $response = yield from $this->getClient()->consume(
                    $this->getChannelId(), $queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments
                );

                $this->deliverCallbacks[$response->consumerTag] = $callback;

                return $response;
            },
            $callback, $queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments
        );
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @param string $consumerTag
     * @param bool   $nowait
     *
     * @return Promise
     */
    public function cancel($consumerTag, $nowait = false): Promise
    {
        /** @var \Generator $generator */
        $generator = $this->getClient()->cancel($consumerTag, $nowait);

        return new Coroutine($generator);
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param string      $body
     * @param array       $headers
     * @param string      $exchange
     * @param string|null $routingKey
     * @param bool        $mandatory
     * @param bool        $immediate
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
        /** @var Promise $promise */
        $promise = $this->getClient()->publish(
            $this->getChannelId(), $body, $headers, $exchange, $routingKey, $mandatory, $immediate
        );

        return $promise;
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress MoreSpecificImplementedParamType Cannot specify data type
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @param Message $message
     * @param bool    $multiple
     *
     * @return Promise
     */
    public function ack(Message $message, $multiple = false): Promise
    {
        /** @var Promise $promise */
        $promise = $this->getClient()->ack($this->getChannelId(), $message->deliveryTag, $multiple);

        return $promise;
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress MoreSpecificImplementedParamType Cannot specify data type
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @param int  $deliveryTag
     * @param bool $requeue
     *
     * @return Promise
     */
    public function reject($deliveryTag, $requeue = true): Promise
    {
        /** @var Promise $promise */
        $promise = $this->getClient()->reject($this->getChannelId(), $deliveryTag, $requeue);

        return $promise;
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress MoreSpecificImplementedParamType Cannot specify data type
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @param int  $deliveryTag
     * @param bool $multiple
     * @param bool $requeue
     *
     * @return Promise
     */
    public function nack($deliveryTag = 0, $multiple = false, $requeue = true): Promise
    {
        /** @var Promise $promise */
        $promise = $this->getClient()->nack($this->getChannelId(), $deliveryTag, $multiple, $requeue);

        return $promise;
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @return Promise
     */
    public function exchangeDelete($exchange, $ifUnused = false, $nowait = false): Promise
    {
        /** @var \Generator $generator */
        $generator = $this->getClient()->exchangeDelete($this->getChannelId(), $exchange, $ifUnused, $nowait);

        return new Coroutine($generator);
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @param string $exchange
     * @param string $exchangeType
     * @param bool   $passive
     * @param bool   $durable
     * @param bool   $autoDelete
     * @param bool   $internal
     * @param bool   $nowait
     * @param array  $arguments
     *
     * @return Promise
     */
    public function exchangeDeclare(
        $exchange,
        $exchangeType = 'direct',
        $passive = false,
        $durable = false,
        $autoDelete = false,
        $internal = false,
        $nowait = false,
        $arguments = []
    ): Promise
    {
        /** @var \Generator $generator */
        $generator = $this->getClient()->exchangeDeclare(
            $this->getChannelId(), $exchange, $exchangeType, $passive, $durable, $autoDelete, $internal, $nowait, $arguments
        );

        return new Coroutine($generator);
    }

    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    protected function onBodyComplete(): void
    {
        /** @psalm-suppress  RedundantConditionGivenDocblockType Incorrect bunny contract */
        if(null !== $this->returnFrame)
        {
            $this->processReturnFrame();

            return;
        }

        /** @psalm-suppress  RedundantConditionGivenDocblockType Incorrect bunny contract */
        if(null !== $this->deliverFrame)
        {
            $this->processDeliverFrame();

            return;
        }

        /** @psalm-suppress  RedundantConditionGivenDocblockType Incorrect bunny contract */
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

        $returnFrame = $this->returnFrame;

        foreach($this->returnCallbacks as $callback)
        {
            /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
            asyncCall($callback, $message, $returnFrame);
        }

        unset($returnFrame);

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
        $this->returnFrame = null;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
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

            /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
            asyncCall($callback, $message, $this, $this->client);
        }

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
        $this->deliverFrame = null;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
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
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
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

        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
        $this->getOkFrame = null;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue Incorrect bunny contract */
        $this->headerFrame = null;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @throws \LogicException Not implemented
     */
    public function get($queue = '', $noAck = false): void
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @throws \LogicException Not implemented
     */
    public function run(callable $callback, $queue = '', $consumerTag = '', $noLocal = false, $noAck = false, $exclusive = false, $nowait = false, $arguments = []): void
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @throws \LogicException Not implemented
     */
    public function confirmSelect(callable $callback = null, $nowait = false): void
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @throws \LogicException Not implemented
     */
    public function txSelect(): void
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @throws \LogicException Not implemented
     */
    public function txCommit(): void
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     * @psalm-suppress ImplementedReturnTypeMismatch The data type has been changed
     *
     * @throws \LogicException Not implemented
     */
    public function txRollback(): void
    {
        throw new \LogicException('Not implemented');
    }
}
