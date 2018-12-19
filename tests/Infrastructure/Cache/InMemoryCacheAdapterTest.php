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

namespace Desperado\ServiceBus\Tests\Infrastructure\Cache;

use Amp\Loop;
use function Amp\Promise\wait;
use Desperado\ServiceBus\Infrastructure\Cache\InMemoryCacheAdapter;
use Desperado\ServiceBus\Infrastructure\Cache\InMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class InMemoryCacheAdapterTest extends TestCase
{
    /**
     * @var InMemoryCacheAdapter
     */
    private $adapter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        InMemoryStorage::instance()->reset();

        $this->adapter = new InMemoryCacheAdapter();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        InMemoryStorage::instance()->reset();

        unset($this->adapter);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function getUnknownEntry(): void
    {
        static::assertNull(wait($this->adapter->get('qwerty')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function hasUnknownEntry(): void
    {
        static::assertFalse(wait($this->adapter->has('qwerty')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function removeUnknownEntry(): void
    {
        wait($this->adapter->remove('qwerty'));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function clear(): void
    {
        wait($this->adapter->save('qwerty', 'root', 50));
        static::assertTrue(wait($this->adapter->has('qwerty')));
        wait($this->adapter->clear());
        static::assertFalse(wait($this->adapter->has('qwerty')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function getExpired(): void
    {
        wait($this->adapter->save('qwerty', 'root', 1));
        \sleep(2);
        static::assertNull(wait($this->adapter->get('qwerty')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function getExists(): void
    {
        wait($this->adapter->save('qwerty', 'root', 50));
        static::assertSame('root', wait($this->adapter->get('qwerty')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function hasExists(): void
    {
        wait($this->adapter->save('qwerty', 'root', 50));
        static::assertTrue(wait($this->adapter->has('qwerty')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function removeExists(): void
    {
        wait($this->adapter->save('qwerty', 'root', 50));
        static::assertTrue(wait($this->adapter->has('qwerty')));

        wait($this->adapter->remove('qwerty'));
        static::assertFalse(wait($this->adapter->has('qwerty')));
    }
}
