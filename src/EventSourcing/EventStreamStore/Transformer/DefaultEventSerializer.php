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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageDecoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;

/**
 *
 */
final class DefaultEventSerializer implements AggregateEventSerializer
{
    /**
     * @var MessageEncoder
     */
    private $encoder;

    /**
     * @var MessageDecoder
     */
    private $decoder;

    /**
     * @param MessageEncoder|null $encoder
     * @param MessageDecoder|null $decoder
     */
    public function __construct(?MessageEncoder $encoder = null, ?MessageDecoder $decoder = null)
    {
        $this->encoder = $encoder ?? new SymfonyMessageSerializer();
        $this->decoder = $decoder ?? new SymfonyMessageSerializer();
    }

    /**
     * @inheritdoc
     */
    public function serialize(Event $event): string
    {
        return $this->encoder->encode($event);
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $eventClass, string $payload): Event
    {
        /** @var Event $event */
        $event = $this->decoder->decode($payload);

        return $event;
    }
}
