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

/**
 * Event serialization
 */
interface AggregateEventSerializer
{
    /**
     * Serialize event object to string
     *
     * @param Event $event
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function serialize(Event $event): string;

    /**
     * Restore event object
     *
     * @param string $eventClass
     * @param string $payload
     *
     * @return Event
     *
     * @throws \RuntimeException
     */
    public function unserialize(string $eventClass, string $payload): Event;
}
