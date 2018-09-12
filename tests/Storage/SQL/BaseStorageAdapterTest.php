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

namespace Desperado\ServiceBus\Tests\Storage\SQL;

use Amp\Coroutine;
use Amp\Promise;
use function Amp\Promise\wait;
use Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed;
use Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed;
use function Desperado\ServiceBus\Storage\fetchAll;
use function Desperado\ServiceBus\Storage\fetchOne;
use Desperado\ServiceBus\Storage\ResultSet;
use Desperado\ServiceBus\Storage\StorageAdapter;
use PHPUnit\Framework\TestCase;

/**
 *
 */
abstract class BaseStorageAdapterTest extends TestCase
{
    /**
     * Get database adapter
     *
     * @return StorageAdapter
     */
    abstract protected static function getAdapter(): StorageAdapter;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $adapter = static::getAdapter();

        wait(
            $adapter->execute(
                'CREATE TABLE IF NOT EXISTS storage_test_table (id UUID, identifier_class VARCHAR NOT NULL, payload BYTEA null , CONSTRAINT identifier PRIMARY KEY (id, identifier_class))'
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

        $adapter = static::getAdapter();

        wait($adapter->execute('DELETE FROM storage_test_table'));
    }


    /**
     * @test
     *
     * @return void
     */
    public function supportsTransaction(): void
    {
        static::assertTrue(static::getAdapter()->supportsTransaction());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function unescapeBinary(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();

                $data = \sha1(\random_bytes(256));

                yield $adapter->execute(
                    'INSERT INTO storage_test_table (id, identifier_class, payload) VALUES (?, ?, ?), (?, ?, ?)',
                    [
                        '77961031-fd0f-4946-b439-dfc2902b961a', 'SomeIdentifierClass', $data,
                        '81c3f1d1-1f75-478e-8bc6-2bb02cd381be', 'SomeIdentifierClass2', \sha1(\random_bytes(256))
                    ]
                );

                /** @var ResultSet $iterator */
                $iterator = yield $adapter->execute('SELECT * from storage_test_table WHERE id = ?', ['77961031-fd0f-4946-b439-dfc2902b961a']);
                $result   = yield fetchAll($iterator);
                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertCount(1, $result);

                static::assertEquals($data, $adapter->unescapeBinary($result[0]['payload']));
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail($throwable->getMessage());
            }
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
    public function resultSet(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();
                yield self::importSagasFixtures($adapter);
                /** @var ResultSet $iterator */
                $iterator = yield $adapter->execute('SELECT * from storage_test_table');
                $result   = yield fetchAll($iterator);

                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertCount(2, $result);
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail($throwable->getMessage());
            }
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
    public function emptyResultSet(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter  = static::getAdapter();
                $iterator = yield $adapter->execute('SELECT * from storage_test_table');
                $result   = yield fetchAll($iterator);

                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertEmpty($result);
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail($throwable->getMessage());
            }
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
    public function failedQuery(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();

                yield $adapter->execute('SELECT abube from storage_test_table');
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertInstanceOf(StorageInteractingFailed::class, $throwable);
            }
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
    public function findOne(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();
                yield self::importSagasFixtures($adapter);
                /** @var ResultSet $iterator */
                $iterator = yield $adapter->execute(
                    'SELECT * from storage_test_table WHERE identifier_class = ?',
                    ['SomeIdentifierClass2']
                );
                $result   = yield fetchOne($iterator);

                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertArrayHasKey('identifier_class', $result);
                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertEquals('SomeIdentifierClass2', $result['identifier_class']);
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail($throwable->getMessage());
            }
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
    public function findOneWhenEmptySet(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();
                /** @var ResultSet $iterator */
                $iterator = yield $adapter->execute(
                    'SELECT * from storage_test_table WHERE identifier_class = ?',
                    ['SomeIdentifierClass2']
                );
                $result   = yield fetchOne($iterator);

                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertEmpty($result);
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail($throwable->getMessage());
            }
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
    public function findOneWhenWrongSet(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();
                yield self::importSagasFixtures($adapter);
                /** @var ResultSet $iterator */
                $iterator = yield $adapter->execute('SELECT * from storage_test_table');
                yield fetchOne($iterator);

                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail('Exception expected');
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertInstanceOf(StorageInteractingFailed::class, $throwable);
                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertEquals(
                    'A single record was requested, but the result of the query execution contains several ("2")',
                    $throwable->getMessage()
                );
            }
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
    public function uniqueKeyCheckFailed(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();

                yield $adapter->execute(
                    'INSERT INTO storage_test_table (id, identifier_class) VALUES (?, ?), (?, ?)',
                    [
                        '77961031-fd0f-4946-b439-dfc2902b961a', 'SomeIdentifierClass',
                        '77961031-fd0f-4946-b439-dfc2902b961a', 'SomeIdentifierClass'
                    ]
                );
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->assertInstanceOf(UniqueConstraintViolationCheckFailed::class, $throwable);
            }
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
    public function rowsCount(): void
    {
        $handler = static function(BaseStorageAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
                $result = yield $adapter->execute(
                    'INSERT INTO storage_test_table (id, identifier_class) VALUES (?, ?), (?, ?)',
                    [
                        '77961031-fd0f-4946-b439-dfc2902b961a', 'SomeIdentifierClass',
                        '77961031-fd0f-4946-b439-dfc2902b961d', 'SomeIdentifierClass'
                    ]
                );

                static::assertSame(2, $result->rowsCount());

                unset($result);

                /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
                $result = yield $adapter->execute(
                    'DELETE FROM storage_test_table where id = \'77961031-fd0f-4946-b439-dfc2902b961d\''
                );

                static::assertSame(1, $result->rowsCount());

                unset($result);

                yield $adapter->execute('DELETE FROM storage_test_table');

                /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
                $result = yield $adapter->execute('DELETE FROM storage_test_table');

                static::assertSame(0, $result->rowsCount());

                unset($result);

                /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
                $result = yield $adapter->execute(
                    'SELECT * FROM storage_test_table where id = \'77961031-fd0f-4946-b439-dfc2902b961d\''
                );

                static::assertSame(0, $result->rowsCount());

                unset($result);
            }
            catch(\Throwable $throwable)
            {
                /** @noinspection StaticInvocationViaThisInspection */
                $self->fail($throwable->getMessage());
            }
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @param StorageAdapter $adapter
     *
     * @return Promise
     */
    private static function importSagasFixtures(StorageAdapter $adapter): Promise
    {
        return $adapter->execute(
            'INSERT INTO storage_test_table (id, identifier_class) VALUES (?, ?), (?, ?)',
            [
                '77961031-fd0f-4946-b439-dfc2902b961a', 'SomeIdentifierClass',
                '81c3f1d1-1f75-478e-8bc6-2bb02cd381be', 'SomeIdentifierClass2'
            ]
        );
    }
}
