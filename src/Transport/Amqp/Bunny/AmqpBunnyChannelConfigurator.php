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

use function Amp\Promise\wait;
use Bunny\Channel;
use Desperado\ServiceBus\Transport\Amqp\AmqpExchange;
use Desperado\ServiceBus\Transport\Amqp\AmqpQueue;
use Desperado\ServiceBus\Transport\Exceptions\BindFailed;
use Desperado\ServiceBus\Transport\Exceptions\CreateQueueFailed;
use Desperado\ServiceBus\Transport\Exceptions\CreateTopicFailed;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

/**
 *
 */
final class AmqpBunnyChannelConfigurator
{

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Channel         $channel
     * @param LoggerInterface $logger
     */
    public function __construct(Channel $channel, LoggerInterface $logger)
    {
        $this->channel = $channel;
        $this->logger  = $logger;
    }

    /**
     * @param AmqpExchange $exchange
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\CreateTopicFailed
     */
    public function addExchange(AmqpExchange $exchange): void
    {
        try
        {
            /** @var PromiseInterface $promise */
            $promise = $this->channel->exchangeDeclare(
                (string) $exchange,
                $exchange->type(),
                $exchange->isPassive(),
                $exchange->isDurable(),
                false,
                false,
                false,
                $exchange->arguments()
            );

            /** force promise resolve */
            wait($promise);

            $this->logger->info('Added exchange "{exchangeName}"', ['exchangeName' => (string) $exchange]);
        }
        catch(\Throwable $throwable)
        {
            throw new CreateTopicFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param AmqpQueue $queue
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\CreateQueueFailed
     */
    public function addQueue(AmqpQueue $queue): void
    {
        try
        {
            /** @var PromiseInterface $promise */
            $promise = $this->channel->queueDeclare(
                (string) $queue,
                $queue->isPassive(),
                $queue->isDurable(),
                $queue->isExclusive(),
                $queue->autoDeleteEnabled(),
                false,
                $queue->arguments()
            );

            /** force promise resolve */
            wait($promise);

            $this->logger->info('Added queue "{queueName}"', ['queueName' => (string) $queue]);
        }
        catch(\Throwable $throwable)
        {
            throw new CreateQueueFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param string $sourceTopic
     * @param string $destinationTopic
     * @param string $routingKey
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\BindFailed
     */
    public function bindTopic(string $sourceTopic, string $destinationTopic, string $routingKey): void
    {
        try
        {
            /** @var PromiseInterface $promise */
            $promise = $this->channel->exchangeBind($destinationTopic, $sourceTopic, $routingKey);

            /** force promise resolve */
            wait($promise);

            $this->logger->info(
                'Added route for exchanger "{sourceExchange}" and "{destinationExchange}" with key "{routingKey}"', [
                    'sourceExchange'      => $sourceTopic,
                    'destinationExchange' => $destinationTopic,
                    'routingKey'          => $routingKey

                ]
            );
        }
        catch(\Throwable $throwable)
        {
            throw new BindFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\BindFailed
     */
    public function bindQueue(string $queue, string $exchange, string $routingKey): void
    {
        try
        {
            /** @var PromiseInterface $promise */
            $promise = $this->channel->queueBind($queue, $exchange, $routingKey);

            /** force promise resolve */
            wait($promise);

            $this->logger->info(
                'Added route for queue "{queueName}" and "{destinationExchange}" with key "{routingKey}"', [
                    'sourceExchange'      => $queue,
                    'destinationExchange' => $exchange,
                    'routingKey'          => $routingKey

                ]
            );
        }
        catch(\Throwable $throwable)
        {
            throw new BindFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}
