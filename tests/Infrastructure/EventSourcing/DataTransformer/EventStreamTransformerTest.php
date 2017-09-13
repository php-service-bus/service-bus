<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Tests\Infrastructure\EventSourcing\DataTransformer;

use Desperado\Framework\Application\Serializer\MessageSerializer;
use Desperado\Framework\Domain\Event\DomainEvent;
use Desperado\Framework\Domain\Event\DomainEventStream;
use Desperado\Framework\Infrastructure\Bridge\Serializer\SymfonySerializer;
use Desperado\Framework\Infrastructure\EventSourcing\DataTransformer\EventStreamTransformer;
use Desperado\Framework\Tests\TestFixtures\Events\SomeEvent;
use Desperado\Framework\Tests\TestFixtures\Identity\TestIdentity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class EventStreamTransformerTest extends TestCase
{
    /**
     * Data transformer
     *
     * @var EventStreamTransformer
     */
    private $transformer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new EventStreamTransformer(
            new MessageSerializer(
                new SymfonySerializer()
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->transformer);
    }

    /**
     * @test
     *
     * @return void
     */
    public function toStoredStream(): void
    {
        $events = [
            DomainEvent::new(new SomeEvent(), -1),
            DomainEvent::new(new SomeEvent(), -0),
            DomainEvent::new(new SomeEvent(), 1),
            DomainEvent::new(new SomeEvent(), 2),
            DomainEvent::new(new SomeEvent(), 3)
        ];

        $eventStream = DomainEventStream::create($events, false);

        $storedDomainEventStream = $this->transformer->toStoredStream(new TestIdentity('streamId'), $eventStream);
        $storedRepresentation = $storedDomainEventStream->toArray();

        static::assertArrayHasKey('id', $storedRepresentation);
        static::assertArrayHasKey('class', $storedRepresentation);
        static::assertArrayHasKey('isClosed', $storedRepresentation);
        static::assertArrayHasKey('events', $storedRepresentation);

        static::assertCount(5, $storedRepresentation['events']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function fromStoredEventStreamData(): void
    {
        $streamData = [
            'id'       => 'streamId',
            'class'    => TestIdentity::class,
            'isClosed' => true,
            'events'   => [
                -1 => ['id'            => '75215c3f-bb37-4fbc-aa53-10c69c61a935',
                       'playhead'      => -1,
                       'receivedEvent' => '{"message":{"someEventId":null,"someEventValue":null},"namespace":"Desperado\\\\Framework\\\\Tests\\\\TestFixtures\\\\Events\\\\SomeEvent","metadata":[]}',
                       'occurredAt'    => '2017-09-13T07:26:09.030405+00:00',
                       'recordedAt'    => '2017-09-13T07:26:09.178338+00:00',
                ],
                0  => [
                    'id'            => '7b9a7da5-095f-4c2b-9dd7-d498dac1fb11',
                    'playhead'      => 0,
                    'receivedEvent' => '{"message":{"someEventId":null,"someEventValue":null},"namespace":"Desperado\\\\Framework\\\\Tests\\\\TestFixtures\\\\Events\\\\SomeEvent","metadata":[]}',
                    'occurredAt'    => '2017-09-13T07:26:09.030512+00:00',
                    'recordedAt'    => '2017-09-13T07:26:09.178453+00:00',
                ],
                1  => [
                    'id'            => 'de07b1ba-1567-4251-a813-a678924369a1',
                    'playhead'      => 1,
                    'receivedEvent' => '{"message":{"someEventId":null,"someEventValue":null},"namespace":"Desperado\\\\Framework\\\\Tests\\\\TestFixtures\\\\Events\\\\SomeEvent","metadata":[]}',
                    'occurredAt'    => '2017-09-13T07:26:09.030544+00:00',
                    'recordedAt'    => '2017-09-13T07:26:09.178567+00:00',
                ],
                2  => [
                    'id'            => '80041e46-0170-4883-a893-6bbca82a960d',
                    'playhead'      => 2,
                    'receivedEvent' => '{"message":{"someEventId":null,"someEventValue":null},"namespace":"Desperado\\\\Framework\\\\Tests\\\\TestFixtures\\\\Events\\\\SomeEvent","metadata":[]}',
                    'occurredAt'    => '2017-09-13T07:26:09.030569+00:00',
                    'recordedAt'    => '2017-09-13T07:26:09.178647+00:00',
                ],
                3  => [
                    'id'            => '6da4fdbb-61f6-482f-8c43-d8da3f7b6b48',
                    'playhead'      => 3,
                    'receivedEvent' => '{"message":{"someEventId":null,"someEventValue":null},"namespace":"Desperado\\\\Framework\\\\Tests\\\\TestFixtures\\\\Events\\\\SomeEvent","metadata":[]}',
                    'occurredAt'    => '2017-09-13T07:26:09.030592+00:00',
                    'recordedAt'    => '2017-09-13T07:26:09.178723+00:00',
                ],
            ],
        ];

        $eventStream = $this->transformer->fromStoredEventStreamData($streamData);

        static::assertTrue($eventStream->isClosed());
        static::assertCount(5, $eventStream);
    }
}
