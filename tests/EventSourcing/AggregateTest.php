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

namespace Desperado\ServiceBus\Tests\EventSourcing;

use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\EventSourcing\Contract\AggregateClosed;
use Desperado\ServiceBus\EventSourcing\Contract\AggregateCreated;
use Desperado\ServiceBus\Tests\Stubs\EventSourcing\TestAggregate;
use Desperado\ServiceBus\Tests\Stubs\EventSourcing\TestAggregateId;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 *
 */
final class AggregateTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\EventSourcing\Exceptions\AttemptToChangeClosedStream
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function changeClosedStreamState(): void
    {
        $aggregate = new TestAggregate(TestAggregateId::new());

        invokeReflectionMethod($aggregate, 'close');

        /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream $eventStream */
        $eventStream = invokeReflectionMethod($aggregate, 'makeStream');

        $events = $eventStream->events;

        /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent $aggregateEvent */
        $aggregateEvent = \end($events);

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(AggregateClosed::class, $aggregateEvent->event);

        /** @var AggregateClosed $aggregateClosedEvent */
        $aggregateClosedEvent = $aggregateEvent->event;

        static::assertTrue(Uuid::isValid($aggregateClosedEvent->id));
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\DateTimeImmutable::class, $aggregateClosedEvent->datetime);
        static::assertEquals(TestAggregate::class, $aggregateClosedEvent->aggregateClass);
        static::assertEquals(TestAggregateId::class, $aggregateClosedEvent->idClass);

        $aggregate->firstAction('root');
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function aggregateCreate(): void
    {
        $aggregate = new TestAggregate(TestAggregateId::new());

        /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream $eventStream */
        $eventStream = invokeReflectionMethod($aggregate, 'makeStream');

        $events = $eventStream->events;

        /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEvent $aggregateEvent */
        $aggregateEvent = \end($events);

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(AggregateCreated::class, $aggregateEvent->event);

        /** @var AggregateCreated $aggregateCreatedEvent */
        $aggregateCreatedEvent = $aggregateEvent->event;

        static::assertTrue(Uuid::isValid($aggregateCreatedEvent->id));
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\DateTimeImmutable::class, $aggregateCreatedEvent->datetime);
        static::assertEquals(TestAggregate::class, $aggregateCreatedEvent->aggregateClass);
        static::assertEquals(TestAggregateId::class, $aggregateCreatedEvent->idClass);
    }
}
