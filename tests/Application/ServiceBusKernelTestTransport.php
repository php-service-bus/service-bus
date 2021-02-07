<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Topic;
use ServiceBus\Transport\Common\TopicBind;
use ServiceBus\Transport\Common\Transport;

/**
 *
 */
final class ServiceBusKernelTestTransport implements Transport
{
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise
    {
        return new Success();
    }

    public function createQueue(Queue $queue, QueueBind ...$binds): Promise
    {
        return new Success();
    }

    public function consume(callable $onMessage, Queue ...$queues): Promise
    {
        return new Success();
    }

    public function stop(): Promise
    {
        return new Success();
    }

    public function send(OutboundPackage ...$outboundPackages): Promise
    {
        return new Success();
    }

    public function connect(): Promise
    {
        return new Success();
    }

    public function disconnect(): Promise
    {
        return new Success();
    }
}
