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

use function Amp\Promise\wait;
use Desperado\ServiceBus\Index\IndexKey;
use Desperado\ServiceBus\Index\IndexValue;
use Desperado\ServiceBus\Index\Storage\Sql\SqlIndexesStorage;
use Desperado\ServiceBus\IndexProvider;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class IndexProviderTest extends TestCase
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @var SqlIndexesStorage
     */
    private $storage;

    /**
     * @var IndexProvider
     */
    private $indexer;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = StorageAdapterFactory::create(
            AmpPostgreSQLAdapter::class,
            (string) \getenv('TEST_POSTGRES_DSN')
        );
        $this->storage = new SqlIndexesStorage($this->adapter);
        $this->indexer = new IndexProvider($this->storage);

        wait(
            $this->adapter->execute(
                \file_get_contents(__DIR__ . '/../src/Index/Storage/Sql/schema/event_sourcing_indexes.sql')
            )
        );
    }

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        wait($this->adapter->execute('DROP TABLE event_sourcing_indexes'));

        unset($this->storage, $this->adapter, $this->indexer);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function save(): void
    {
        $index = IndexKey::create(__CLASS__, 'testKey');
        $value = IndexValue::create(__METHOD__);

        /** @var bool $result */
        $result = wait($this->indexer->add($index, $value));

        static::assertThat($result, new IsType('bool'));
        static::assertTrue($result);

        /** @var IndexValue|null $storedValue */
        $storedValue = wait($this->indexer->get($index));

        static::assertNotNull($storedValue);
        static::assertEquals($value->value(), $storedValue->value());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveDuplicate(): void
    {
        $index = IndexKey::create(__CLASS__, 'testKey');
        $value = IndexValue::create(__METHOD__);

        /** @var bool $result */
        $result = wait($this->indexer->add($index, $value));

        static::assertThat($result, new IsType('bool'));
        static::assertTrue($result);


        /** @var bool $result */
        $result = wait($this->indexer->add($index, $value));

        static::assertThat($result, new IsType('bool'));
        static::assertFalse($result);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function update(): void
    {
        $index = IndexKey::create(__CLASS__, 'testKey');
        $value = IndexValue::create(__METHOD__);

        wait($this->indexer->add($index, $value));

        $newValue = IndexValue::create('qwerty');

        wait($this->indexer->update($index, $newValue));

        /** @var IndexValue|null $storedValue */
        $storedValue = wait($this->indexer->get($index));

        static::assertNotNull($storedValue);
        static::assertEquals($newValue->value(), $storedValue->value());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function remove(): void
    {
        $index = IndexKey::create(__CLASS__, 'testKey');
        $value = IndexValue::create(__METHOD__);

        wait($this->indexer->add($index, $value));
        wait($this->indexer->remove($index));

        static::assertNull(wait($this->indexer->get($index)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function has(): void
    {
        $index = IndexKey::create(__CLASS__, 'testKey');
        $value = IndexValue::create(__METHOD__);

        static::assertFalse(wait($this->indexer->has($index)));

        wait($this->indexer->add($index, $value));

        static::assertTrue(wait($this->indexer->has($index)));
    }
}
