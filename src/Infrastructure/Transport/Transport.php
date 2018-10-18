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

namespace Desperado\ServiceBus\Infrastructure\Transport;

use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;

/**
 * Messages transport interface
 */
interface Transport
{
    /**
     * Create topic and bind them
     * If the topic to which we binds does not exist, it will be created
     *
     * @param Topic     $topic
     * @param TopicBind ...$binds
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\ConnectionFail Connection refused
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\CreateTopicFailed Failed to create topic
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\BindFailed Failed topic bind
     */
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise;

    /**
     * Create queue and bind to topic(s)
     * If the topic to which we binds does not exist, it will be created
     *
     * @param Queue     $queue
     * @param QueueBind ...$binds
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\ConnectionFail Connection refused
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\CreateQueueFailed Failed to create queue
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\BindFailed Failed queue bind
     */
    public function createQueue(Queue $queue, QueueBind ...$binds): Promise;

    /**
     * Consume to queue
     *
     * @return Promise<\Amp\Iterator<\Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage>>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\ConnectionFail Connection refused
     */
    public function consume(Queue $queue): Promise;

    /**
     * Stop subscription
     *
     * @param Queue $queue
     *
     * @return Promise<null>
     */
    public function stop(Queue $queue): Promise;

    /**
     * Send message to broker
     *
     * @param OutboundPackage $outboundPackage
     *
     * @return Promise<null>
     */
    public function send(OutboundPackage $outboundPackage): Promise;

    /**
     * Connect to broker
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\ConnectionFail Connection refused
     */
    public function connect(): Promise;

    /**
     * Close connection
     *
     * @return Promise<null>
     */
    public function disconnect(): Promise;
}
