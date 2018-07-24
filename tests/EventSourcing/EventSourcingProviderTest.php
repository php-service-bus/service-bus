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

use Amp\Coroutine;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\Contract\EventSourcing\AggregateCreated;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\AggregateStore;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Sql\SqlEventStreamStore;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer\DefaultEventSerializer;
use Desperado\ServiceBus\EventSourcingProvider;
use Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\SnapshotStore;
use Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\SqlSnapshotStore;
use Desperado\ServiceBus\EventSourcingSnapshots\Snapshotter;
use Desperado\ServiceBus\EventSourcingSnapshots\Trigger\SnapshotVersionTrigger;
use Desperado\ServiceBus\Marshal\Denormalizer\SymfonyPropertyDenormalizer;
use Desperado\ServiceBus\Marshal\Normalizer\SymfonyPropertyNormalizer;
use Desperado\ServiceBus\Marshal\Serializer\SymfonyJsonSerializer;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\EventSourcing\Mocks\TestAggregate;
use Desperado\ServiceBus\Tests\EventSourcing\Mocks\TestAggregateContext;
use Desperado\ServiceBus\Tests\EventSourcing\Mocks\TestAggregateId;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class EventSourcingProviderTest extends TestCase
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @var AggregateStore
     */
    private $store;

    /**
     * @var SnapshotStore
     */
    private $snapshotterStorage;

    /**
     * @var Snapshotter
     */
    private $snapshotter;

    /**
     * @var EventSourcingProvider
     */
    private $provider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->adapter = StorageAdapterFactory::inMemory();
        $this->store   = new SqlEventStreamStore($this->adapter);

        $this->snapshotterStorage = new SqlSnapshotStore($this->adapter);
        $this->snapshotter        = new Snapshotter(
            $this->snapshotterStorage,
            new SnapshotVersionTrigger(1)
        );

        $this->provider = new EventSourcingProvider(
            $this->store,
            new DefaultEventSerializer(
                new SymfonyJsonSerializer(),
                new SymfonyPropertyNormalizer(),
                new SymfonyPropertyDenormalizer()
            ),
            $this->snapshotter
        );

        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->adapter, $this->store, $this->provider, $this->snapshotter, $this->snapshotterStorage);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function flow(): void
    {
        $handler = static function(EventSourcingProviderTest $self): \Generator
        {
            yield $self->adapter->execute(
                \file_get_contents(__DIR__ . '/../../src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql')
            );

            yield $self->adapter->execute(
                \file_get_contents(__DIR__ . '/../../src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql')
            );

            yield $self->adapter->execute(
                \file_get_contents(__DIR__ . '/../../src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql')
            );

            $context = new TestAggregateContext();

            $aggregate = new TestAggregate(TestAggregateId::new());

            yield $self->provider->save($aggregate, $context);

            $toPublish = $context->messages;

            static::assertCount(1, $toPublish);

            /** @var AggregateCreated $event */
            $event = \end($toPublish);

            static::assertInstanceOf(AggregateCreated::class, $event);

            $loadedAggregate = yield $self->provider->load($aggregate->id());

            static::assertNotNull($loadedAggregate);
            static::assertInstanceOf(Aggregate::class, $loadedAggregate);

            /** @var Aggregate $loadedAggregate */

            static::assertEquals(1, $loadedAggregate->version());

            /** @var AggregateEventStream $stream */
            $stream = invokeReflectionMethod($loadedAggregate, 'makeStream');

            static::assertCount(0, $stream->events());

            yield $self->provider->save($loadedAggregate, $context);
        };

        wait(new Coroutine($handler($this)));
    }
}
