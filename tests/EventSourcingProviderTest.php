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

namespace Desperado\ServiceBus\Tests;

use function Amp\call;
use Amp\Promise;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\Contract\AggregateCreated;
use Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\AggregateStore;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Sql\SqlEventStreamStore;
use Desperado\ServiceBus\EventSourcingProvider;
use Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\SnapshotStore;
use Desperado\ServiceBus\EventSourcingSnapshots\SnapshotStore\SqlSnapshotStore;
use Desperado\ServiceBus\EventSourcingSnapshots\Snapshotter;
use Desperado\ServiceBus\EventSourcingSnapshots\Trigger\SnapshotVersionTrigger;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\EventSourcing\TestAggregate;
use Desperado\ServiceBus\Tests\Stubs\EventSourcing\TestAggregateId;
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
        $this->adapter = StorageAdapterFactory::create(
            AmpPostgreSQLAdapter::class,
            (string) \getenv('TEST_POSTGRES_DSN')
        );
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
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        wait($this->adapter->execute('TRUNCATE TABLE event_store_stream CASCADE'));

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
        wait(self::createSchema($this->adapter));

        $context = new TestContext();

        $aggregate = new TestAggregate(TestAggregateId::new(TestAggregate::class));

        wait($this->provider->save($aggregate, $context));

        $toPublish = $context->messages;

        static::assertCount(1, $toPublish);

        /** @var AggregateCreated $event */
        $event = \end($toPublish);

        static::assertInstanceOf(AggregateCreated::class, $event);

        $loadedAggregate = wait($this->provider->load($aggregate->id()));

        static::assertNotNull($loadedAggregate);
        static::assertInstanceOf(Aggregate::class, $loadedAggregate);

        /** @var Aggregate $loadedAggregate */

        static::assertEquals(1, $loadedAggregate->version());

        /** @var AggregateEventStream $stream */
        $stream = invokeReflectionMethod($loadedAggregate, 'makeStream');

        static::assertCount(0, $stream->events);

        wait($this->provider->save($loadedAggregate, $context));
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
        wait(self::createSchema($this->adapter));

        $context = new TestContext();

        $aggregate = new TestAggregate(TestAggregateId::new(TestAggregate::class));

        wait($this->provider->save($aggregate, $context));

        $toPublish = $context->messages;

        static::assertCount(1, $toPublish);

        /** first action */
        $aggregate->firstAction('qwerty');

        wait($this->provider->save($aggregate, $context));

        $toPublish = $context->messages;

        static::assertCount(2, $toPublish);

        /** second action  */
        $aggregate->secondAction('root');

        wait($this->provider->save($aggregate, $context));

        $toPublish = $context->messages;

        static::assertCount(3, $toPublish);


        /** assert values */
        static::assertNotNull($aggregate->firstValue());
        static::assertNotNull($aggregate->secondValue());

        static::assertEquals('qwerty', $aggregate->firstValue());
        static::assertEquals('root', $aggregate->secondValue());
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
        wait(static::createSchema($this->adapter));

        $context = new TestContext();

        $id = TestAggregateId::new(TestAggregate::class);

        $this->expectException(NonUniqueStreamId::class);

        wait($this->provider->save(new TestAggregate($id), $context));
        wait($this->provider->save(new TestAggregate($id), $context));
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
        $provider = new EventSourcingProvider(
            $this->store,
            new Snapshotter(
                $this->snapshotterStorage,
                new SnapshotVersionTrigger(100500)
            )
        );

        wait(self::createSchema($this->adapter));

        $context = new TestContext();

        $id = TestAggregateId::new(TestAggregate::class);

        $aggregate = new TestAggregate($id);

        wait($provider->save($aggregate, $context));

        wait($this->snapshotterStorage->remove($id));

        /** @var \Desperado\ServiceBus\EventSourcing\Aggregate|null $aggregate */
        $aggregate = wait($provider->load($id));

        static::assertNotNull($aggregate);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successSoftDeleteRevert(): void
    {
        wait(self::createSchema($this->adapter));

        $context   = new TestContext();
        $aggregate = new TestAggregate(TestAggregateId::new(TestAggregate::class));

        wait($this->provider->save($aggregate, $context));

        foreach(\range(1, 6) as $item)
        {
            $aggregate->firstAction($item + 1 . ' event');
        }

        /** 7 aggregate version */
        wait($this->provider->save($aggregate, $context));

        /** 7 aggregate version */
        static::assertEquals(7, $aggregate->version());
        static::assertEquals('7 event', $aggregate->firstValue());

        /** @var TestAggregate $aggregate */
        $aggregate = wait($this->provider->revert($aggregate, 5, EventSourcingProvider::REVERT_MODE_SOFT_DELETE));

        static::assertEquals(5, $aggregate->version());
        static::assertEquals('5 event', $aggregate->firstValue());

        foreach(\range(1, 6) as $item)
        {
            $aggregate->firstAction($item + 5 . ' new event');
        }

        /** 7 aggregate version */
        wait($this->provider->save($aggregate, $context));

        static::assertEquals(11, $aggregate->version());
        static::assertEquals('11 new event', $aggregate->firstValue());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successHardDeleteRevert(): void
    {
        wait(self::createSchema($this->adapter));

        $context   = new TestContext();
        $aggregate = new TestAggregate(TestAggregateId::new(TestAggregate::class));

        wait($this->provider->save($aggregate, $context));

        foreach(\range(1, 6) as $item)
        {
            $aggregate->firstAction($item + 1 . ' event');
        }

        /** 7 aggregate version */
        wait($this->provider->save($aggregate, $context));

        /** 7 aggregate version */
        static::assertEquals(7, $aggregate->version());
        static::assertEquals('7 event', $aggregate->firstValue());

        /** @var TestAggregate $aggregate */
        $aggregate = wait($this->provider->revert($aggregate, 5, EventSourcingProvider::REVERT_MODE_DELETE));

        /** 7 aggregate version */
        static::assertEquals(5, $aggregate->version());
        static::assertEquals('5 event', $aggregate->firstValue());

        $eventsCount = wait(
            fetchOne(
                wait($this->adapter->execute('SELECT COUNT(id) as cnt FROM event_store_stream_events'))
            )
        );

        static::assertEquals(5, $eventsCount['cnt']);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\EventStreamDoesNotExist
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function revertUnknownStream(): void
    {
        wait(self::createSchema($this->adapter));
        wait($this->store->revertStream(TestAggregateId::new(TestAggregate::class), 20, true));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\EventStreamIntegrityCheckFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function revertWithVersionConflict(): void
    {
        wait(self::createSchema($this->adapter));

        $context   = new TestContext();
        $aggregate = new TestAggregate(TestAggregateId::new(TestAggregate::class));

        $aggregate->firstAction('qwerty');
        $aggregate->firstAction('root');
        $aggregate->firstAction('qwertyRoot');

        wait($this->provider->save($aggregate, $context));

        /** @var TestAggregate $aggregate */
        $aggregate = wait($this->provider->revert($aggregate, 2, EventSourcingProvider::REVERT_MODE_SOFT_DELETE));

        $aggregate->firstAction('abube');

        wait($this->provider->save($aggregate, $context));
        wait($this->provider->revert($aggregate, 3, EventSourcingProvider::REVERT_MODE_SOFT_DELETE));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createAggregate(): void
    {
        wait(self::createSchema($this->adapter));

        /** @var Aggregate $aggregate */
        $aggregate = wait($this->provider->create(TestAggregateId::new(TestAggregate::class), new TestContext()));

        static::assertEquals(1, $aggregate->version());
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
                    \file_get_contents(__DIR__ . '/../src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql')
                );

                yield $adapter->execute(
                    \file_get_contents(__DIR__ . '/../src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql')
                );

                yield $adapter->execute(
                    'CREATE UNIQUE INDEX IF NOT EXISTS event_store_stream_events_playhead ON event_store_stream_events (stream_id, playhead) WHERE canceled_at IS NULL;'
                );

                yield $adapter->execute(
                    \file_get_contents(__DIR__ . '/../src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql')
                );
            }
        );
    }
}
