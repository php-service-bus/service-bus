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

use function Amp\call;
use Amp\Emitter;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Queue;
use Desperado\ServiceBus\Infrastructure\Transport\QueueBind;
use Desperado\ServiceBus\Infrastructure\Transport\Topic;
use Desperado\ServiceBus\Infrastructure\Transport\TopicBind;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;

/**
 *
 */
class VirtualTransport implements Transport
{


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
    public function consume(Queue $queue): Promise
    {
        $emitter = new Emitter();

        if(true === VirtualTransportBuffer::instance()->has())
        {
            [$messagePayload, $headers] = VirtualTransportBuffer::instance()->extract();

            $emitter->emit(
                new VirtualIncomingPackage($messagePayload, $headers)
            );
        }

        return $emitter->iterate();
    }

    /**
     * @inheritDoc
     */
    public function stop(Queue $queue): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function send(OutboundPackage $outboundPackage): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(OutboundPackage $outboundPackage)
            {
                VirtualTransportBuffer::instance()->add(
                    yield $outboundPackage->payload()->read(), $outboundPackage->headers()
                );
            },
            $outboundPackage
        );
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
