<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport;

/**
 *
 */
interface Transport
{
    /**
     * Create topic
     *
     * @param Topic $topic
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @throws \Desperado\ServiceBus\Transport\Exceptions\CreateTopicFailed
     */
    public function createTopic(Topic $topic): void;

    /**
     * Create queue
     *
     * @param Queue     $queue
     * @param QueueBind $bind
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @throws \Desperado\ServiceBus\Transport\Exceptions\CreateQueueFailed
     */
    public function createQueue(Queue $queue, QueueBind $bind = null): void;

    /**
     * Create publisher
     *
     * @return Publisher
     */
    public function createPublisher(): Publisher;

    /**
     * Create consumer
     *
     * @param Queue          $listenQueue
     *
     * @return Consumer
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\NotConfiguredQueue
     */
    public function createConsumer(Queue $listenQueue): Consumer;

    /**
     * Close context
     *
     * @return void
     */
    public function close(): void;
}
