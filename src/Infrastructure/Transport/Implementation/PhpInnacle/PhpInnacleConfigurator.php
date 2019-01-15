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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\PhpInnacle;

use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\BindFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\CreateQueueFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\CreateTopicFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use PHPinnacle\Ridge\Channel;

/**
 * Creating exchangers\queues and bind them
 *
 * @internal
 */
final class PhpInnacleConfigurator
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @param Channel $channel
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Execute queue creation
     *
     * @param Channel   $channel
     * @param AmqpQueue $queue
     *
     * @return \Generator
     */
    public function doCreateQueue(AmqpQueue $queue): \Generator
    {
        try
        {
            yield $this->channel->queueDeclare(
                (string) $queue, $queue->isPassive(), $queue->isDurable(), $queue->isExclusive(),
                $queue->autoDeleteEnabled(), false, $queue->arguments()
            );
        }
        catch(\Throwable $throwable)
        {
            throw new CreateQueueFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * Bind queue to exchange(s)
     *     $channel
     *
     * @param AmqpQueue                                                              $queue
     * @param array<mixed, \Desperado\ServiceBus\Infrastructure\Transport\QueueBind> $binds
     *
     * @return \Generator
     */
    public function doBindQueue(AmqpQueue $queue, array $binds): \Generator
    {
        try
        {
            foreach($binds as $bind)
            {
                /** @var \Desperado\ServiceBus\Infrastructure\Transport\QueueBind $bind */

                /** @var AmqpExchange $destinationExchange */
                $destinationExchange = $bind->topic();

                yield from $this->doCreateExchange($destinationExchange);

                yield $this->channel->queueBind((string) $queue, (string) $destinationExchange, (string) $bind->routingKey());
            }
        }
        catch(\Throwable $throwable)
        {
            throw new BindFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * Execute exchange creation
     *
     * @param AmqpExchange $exchange
     *
     * @return \Generator
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\CreateTopicFailed
     */
    public function doCreateExchange(AmqpExchange $exchange): \Generator
    {
        try
        {
            yield $this->channel->exchangeDeclare(
                (string) $exchange, $exchange->type(), $exchange->isPassive(), $exchange->isDurable(),
                false, false, false, $exchange->arguments()
            );
        }
        catch(\Throwable $throwable)
        {
            throw new CreateTopicFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * Bind exchange to another exchange(s)
     *
     * @param AmqpExchange                                                           $exchange
     * @param array<mixed, \Desperado\ServiceBus\Infrastructure\Transport\TopicBind> $binds
     *
     * @return \Generator
     */
    public function doBindExchange(AmqpExchange $exchange, array $binds): \Generator
    {
        try
        {
            foreach($binds as $bind)
            {
                /** @var \Desperado\ServiceBus\Infrastructure\Transport\TopicBind $bind */

                /** @var AmqpExchange $sourceExchange */
                $sourceExchange = $bind->topic();

                yield from $this->doCreateExchange($sourceExchange);
                yield $this->channel->exchangeBind((string) $sourceExchange, (string) $exchange, (string) $bind->routingKey());
            }
        }
        catch(\Throwable $throwable)
        {
            throw new BindFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }
}
