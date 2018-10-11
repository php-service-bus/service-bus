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

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageDecoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;
use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\Topic;
use Desperado\ServiceBus\Transport\TopicBind;
use Desperado\ServiceBus\Transport\Transport;

/**
 *
 */
class VirtualTransport implements Transport
{
    /**
     * Restore the message object from string
     *
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * Restore the message object from string
     *
     * @var MessageDecoder
     */
    private $messageDecoder;

    /**
     * @param MessageEncoder|null $messageEncoder
     * @param MessageDecoder|null $messageDecoder
     */
    public function __construct(
        MessageEncoder $messageEncoder = null,
        MessageDecoder $messageDecoder = null
    )
    {
        $this->messageEncoder = $messageEncoder ?? new SymfonyMessageSerializer();
        $this->messageDecoder = $messageDecoder ?? new SymfonyMessageSerializer();
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
    public function bindTopic(TopicBind $to): void
    {

    }

    /**
     * @inheritDoc
     */
    public function bindQueue(QueueBind $to): void
    {

    }

    /**
     * @inheritDoc
     */
    public function createQueue(Queue $queue): void
    {

    }

    /**
     * @inheritDoc
     */
    public function createPublisher(): Publisher
    {
        return new VirtualPublisher($this->messageEncoder);
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
    public function close(): Promise
    {
        return new Success();
    }
}
