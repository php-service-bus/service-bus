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
use PHPUnit\Framework\TestCase;

/**
 *
 */
class StoredDomainEventTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function getStoredDomainEventAsArray(): void
    {
        $currentDate = \date('c');
        $eventId = 'someId';
        $playhead = -1;
        $eventPayload = 'someEventPayload';

        $expectedArray = [
            'id'            => $eventId,
            'playhead'      => $playhead,
            'receivedEvent' => $eventPayload,
            'occurredAt'    => $currentDate,
            'recordedAt'    => $currentDate
        ];

        $storedDomainEvent = new StoredDomainEvent($eventId, $playhead, $eventPayload, $currentDate, $currentDate);

        static::assertEquals($expectedArray, $storedDomainEvent->toArray());
        static::assertEquals($eventId, $storedDomainEvent->getId());
        static::assertEquals($currentDate, $storedDomainEvent->getRecordedAt());
        static::assertEquals($currentDate, $storedDomainEvent->getOccurredAt());
        static::assertEquals($playhead, $storedDomainEvent->getPlayhead());
        static::assertEquals($eventPayload, $storedDomainEvent->getReceivedEvent());
    }
}
