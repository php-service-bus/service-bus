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

namespace Desperado\Framework\Tests\Domain\Event;

use Desperado\Framework\Domain\Event\DomainEvent;
use Desperado\Framework\Domain\Event\DomainEventStream;
use Desperado\Framework\Tests\TestFixtures\Events\SomeEvent;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DomainEventStreamTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function createStream(): void
    {
        $events = [
            DomainEvent::new(new SomeEvent(), -1),
            DomainEvent::new(new SomeEvent(), -0),
            DomainEvent::new(new SomeEvent(), 1),
            DomainEvent::new(new SomeEvent(), 2),
            DomainEvent::new(new SomeEvent(), 3)
        ];

        $eventStream = DomainEventStream::create($events, false);
        $eventStreamHash = \spl_object_hash($eventStream);

        static::assertCount(\count($events), $eventStream);
        static::assertFalse($eventStream->isClosed());
        static::assertInstanceOf(\Iterator::class, $eventStream->getIterator());
        static::assertTrue(\is_iterable($eventStream));

        $closedStream = $eventStream->closeStream();
        $closedStreamHash = \spl_object_hash($closedStream);

        static::assertNotEquals($eventStreamHash, $closedStreamHash);
        static::assertTrue($closedStream->isClosed());
    }
}
