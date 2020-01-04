<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Topic;
use ServiceBus\Transport\Common\TopicBind;
use ServiceBus\Transport\Common\Transport;
use function Amp\call;
use function ServiceBus\Common\jsonEncode;

/**
 *
 */
final class EntryPointTestTransport implements Transport
{
    /** @var object[] */
    public $incomingMessages = [];

    /** @var OutboundPackage[] */
    public $outboundPackageCollection = [];

    public function __construct(array $incomingMessages = [])
    {
        $this->incomingMessages = $incomingMessages;
    }

    /**
     * @inheritDoc
     */
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function createQueue(Queue $queue, QueueBind ...$binds): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function consume(callable $onMessage, Queue ...$queues): Promise
    {
        return call(
            function() use ($onMessage): \Generator
            {
                foreach($this->incomingMessages as $message)
                {
                    yield from $onMessage(
                        new EntryPointTestIncomingPackage(
                            jsonEncode([
                                'namespace' => \get_class($message),
                                'message'   => $message
                            ])
                        )
                    );
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function stop(): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function send(OutboundPackage $outboundPackage): Promise
    {
        $this->outboundPackageCollection[] = $outboundPackage;

        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function connect(): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): Promise
    {
        return new Success();
    }
}
