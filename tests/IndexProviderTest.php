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

use Amp\Coroutine;
use function Amp\Promise\wait;
use Desperado\ServiceBus\Index\IndexKey;
use Desperado\ServiceBus\Index\IndexValue;
use Desperado\ServiceBus\Index\Storage\Sql\SqlIndexesStorage;
use Desperado\ServiceBus\IndexProvider;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
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

        $this->adapter = StorageAdapterFactory::inMemory();
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
     */
    protected function tearDown(): void
    {
        parent::tearDown();

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
        $handler = static function(IndexProviderTest $self): \Generator
        {
            $index = IndexKey::create(__CLASS__, 'testKey');
            $value = IndexValue::create(__METHOD__);

            /** @var bool $result */
            $result = yield $self->indexer->add($index, $value);

            static::assertInternalType('bool', $result);
            static::assertTrue($result);

            /** @var IndexValue|null $storedValue */
            $storedValue = yield $self->indexer->get($index);

            static::assertNotNull($storedValue);
            static::assertEquals($value->value(), $storedValue->value());
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
    public function saveDuplicate(): void
    {
        $handler = static function(IndexProviderTest $self): \Generator
        {
            $index = IndexKey::create(__CLASS__, 'testKey');
            $value = IndexValue::create(__METHOD__);

            /** @var bool $result */
            $result = yield $self->indexer->add($index, $value);

            static::assertInternalType('bool', $result);
            static::assertTrue($result);


            /** @var bool $result */
            $result = yield $self->indexer->add($index, $value);

            static::assertInternalType('bool', $result);
            static::assertFalse($result);
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
    public function update(): void
    {
        $handler = static function(IndexProviderTest $self): \Generator
        {
            $index = IndexKey::create(__CLASS__, 'testKey');
            $value = IndexValue::create(__METHOD__);

            yield $self->indexer->add($index, $value);

            $newValue = IndexValue::create('qwerty');

            yield $self->indexer->update($index, $newValue);

            /** @var IndexValue|null $storedValue */
            $storedValue = yield $self->indexer->get($index);

            static::assertNotNull($storedValue);
            static::assertEquals($newValue->value(), $storedValue->value());
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
    public function remove(): void
    {
        $handler = static function(IndexProviderTest $self): \Generator
        {
            $index = IndexKey::create(__CLASS__, 'testKey');
            $value = IndexValue::create(__METHOD__);

            yield $self->indexer->add($index, $value);
            yield $self->indexer->remove($index);

            static::assertNull(yield $self->indexer->get($index));
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
    public function has(): void
    {
        $handler = static function(IndexProviderTest $self): \Generator
        {
            $index = IndexKey::create(__CLASS__, 'testKey');
            $value = IndexValue::create(__METHOD__);

            static::assertFalse(yield $self->indexer->has($index));

            yield $self->indexer->add($index, $value);

            static::assertTrue(yield $self->indexer->has($index));
        };

        wait(new Coroutine($handler($this)));
    }
}
