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

namespace Desperado\ServiceBus\Tests\Stubs\Transport;

use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\Marshal\Decoder\JsonMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Decoder\TransportMessageDecoder;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\Topic;
use Desperado\ServiceBus\Transport\Transport;

/**
 *
 */
class VirtualTransport implements Transport
{
    /**
     * Restore the message object from string
     *
     * @var TransportMessageDecoder
     */
    private $messageDecoder;

    /**
     * @param TransportMessageDecoder|null $messageDecoder
     */
    public function __construct(TransportMessageDecoder $messageDecoder = null)
    {
        $this->messageDecoder = $messageDecoder ?? new JsonMessageDecoder();
    }

    /**
     * @inheritDoc
     */
    public function createTopic(Topic $topic): void
    {

    }

    /**
     * @inheritDoc
     */
    public function createQueue(Queue $queue, QueueBind $bind = null): void
    {

    }

    /**
     * @inheritDoc
     */
    public function createPublisher(): Publisher
    {
        return new VirtualPublisher();
    }

    /**
     * @inheritDoc
     */
    public function createConsumer(Queue $listenQueue): Consumer
    {
        return new VirtualConsumer($this->messageDecoder);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {

    }

}
