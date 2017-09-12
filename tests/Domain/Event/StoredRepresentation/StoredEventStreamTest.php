<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Tests\Domain\Event\StoredRepresentation;

use Desperado\Framework\Domain\Event\StoredRepresentation\StoredDomainEvent;
use Desperado\Framework\Domain\Event\StoredRepresentation\StoredEventStream;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class StoredEventStreamTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function getStoredDomainEventStreamAsArray(): void
    {
        $currentDate = \date('c');
        $eventId = 'someId';
        $playhead = -1;
        $eventPayload = 'someEventPayload';

        $streamId = 'someStreamId';
        $streamClass = 'someClassNamespace';
        $isClosed = false;

        $storedDomainEvent = new StoredDomainEvent($eventId, $playhead, $eventPayload, $currentDate, $currentDate);
        $storedDomainEvents = [$storedDomainEvent];
        $storedDomainEventStream = new StoredEventStream($streamId, $streamClass, $isClosed, $storedDomainEvents);

        $expectedArray = [
            'id'       => $streamId,
            'class'    => $streamClass,
            'isClosed' => $isClosed,
            'events'   => [
                [
                    'id'            => $eventId,
                    'playhead'      => $playhead,
                    'receivedEvent' => $eventPayload,
                    'occurredAt'    => $currentDate,
                    'recordedAt'    => $currentDate
                ]
            ]
        ];

        static::assertEquals($expectedArray, $storedDomainEventStream->toArray());
        static::assertEquals('someStreamId', $storedDomainEventStream->getId());
        static::assertEquals('someClassNamespace', $storedDomainEventStream->getClass());
        static::assertEquals($storedDomainEvents, $storedDomainEventStream->getEvents());
        static::assertEquals('someClassNamespace:someStreamId', $storedDomainEventStream->getCompositeIndex());
    }
}
