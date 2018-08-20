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

use function Amp\call;
use Amp\Coroutine;
use Amp\Promise;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\Contract\AggregateCreated;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\AggregateStore;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\LoadStreamFailed;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Sql\SqlEventStreamStore;
use Desperado\ServiceBus\EventSourcingProvider;
use Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\SnapshotStore;
use Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\SqlSnapshotStore;
use Desperado\ServiceBus\EventSourcingSnapshots\Snapshotter;
use Desperado\ServiceBus\EventSourcingSnapshots\Trigger\SnapshotVersionTrigger;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\EventSourcing\Stubs\TestAggregate;
use Desperado\ServiceBus\Tests\EventSourcing\Stubs\TestAggregateContext;
use Desperado\ServiceBus\Tests\EventSourcing\Stubs\TestAggregateId;
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
            yield self::createSchema($self->adapter);

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

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function loadWithSnapshot(): void
    {
        $handler = static function(EventSourcingProviderTest $self): \Generator
        {
            yield self::createSchema($self->adapter);

            $context = new TestAggregateContext();

            $aggregate = new TestAggregate(TestAggregateId::new());

            yield $self->provider->save($aggregate, $context);

            $toPublish = $context->messages;

            static::assertCount(1, $toPublish);

            /** first action */
            $aggregate->firstAction('qwerty');

            yield $self->provider->save($aggregate, $context);

            $toPublish = $context->messages;

            static::assertCount(1, $toPublish);

            /** second action  */
            $aggregate->secondAction('root');

            yield $self->provider->save($aggregate, $context);

            $toPublish = $context->messages;

            static::assertCount(1, $toPublish);


            /** assert values */
            static::assertNotNull($aggregate->firstValue());
            static::assertNotNull($aggregate->secondValue());

            static::assertEquals('qwerty', $aggregate->firstValue());
            static::assertEquals('root', $aggregate->secondValue());
        };

        wait(new Coroutine($handler($this)));
    }


    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function loadStreamFailed(): void
    {
        $handler = static function(EventSourcingProviderTest $self): \Generator
        {
            $self->expectException(LoadStreamFailed::class);

            yield $self->provider->load(TestAggregateId::new());
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveDuplicateAggregate(): void
    {
        $handler = static function(EventSourcingProviderTest $self): \Generator
        {
            yield static::createSchema($self->adapter);

            $context = new TestAggregateContext();

            $id = TestAggregateId::new();

            $self->expectException(NonUniqueStreamId::class);

            yield $self->provider->save(new TestAggregate($id), $context);
            yield $self->provider->save(new TestAggregate($id), $context);
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveWithoutSchema(): void
    {
        $handler = static function(EventSourcingProviderTest $self): \Generator
        {
            $self->expectException(SaveStreamFailed::class);

            yield $self->provider->save(new TestAggregate(TestAggregateId::new()), new TestAggregateContext());
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function loadWithoutSnapshot(): void
    {
        $handler = static function(EventSourcingProviderTest $self): \Generator
        {
            $provider = new EventSourcingProvider(
                $self->store,
                new Snapshotter(
                    $self->snapshotterStorage,
                    new SnapshotVersionTrigger(100500)
                )
            );

            yield self::createSchema($self->adapter);

            $context = new TestAggregateContext();

            $id = TestAggregateId::new();

            $aggregate = new TestAggregate($id);

            yield $provider->save($aggregate, $context);

            yield $self->snapshotterStorage->remove($id);

            /** @var \Desperado\ServiceBus\EventSourcing\Aggregate|null $aggregate */
            $aggregate = yield $provider->load($id);

            static::assertNotNull($aggregate);
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @param StorageAdapter $adapter
     *
     * @return Promise<null>
     */
    private static function createSchema(StorageAdapter $adapter): Promise
    {
        return call(
            static function() use ($adapter): \Generator
            {
                yield $adapter->execute(
                    \file_get_contents(__DIR__ . '/../../src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql')
                );

                yield $adapter->execute(
                    \file_get_contents(__DIR__ . '/../../src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql')
                );

                yield $adapter->execute(
                    \file_get_contents(__DIR__ . '/../../src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql')
                );
            }
        );
    }
}
