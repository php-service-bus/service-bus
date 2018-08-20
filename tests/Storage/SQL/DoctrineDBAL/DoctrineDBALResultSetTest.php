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

namespace Desperado\ServiceBus\Tests\Storage\SQL\DoctrineDBAL;

use Amp\Coroutine;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Storage\fetchAll;
use function Desperado\ServiceBus\Storage\fetchOne;
use Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class DoctrineDBALResultSetTest extends TestCase
{
    /**
     * @var DoctrineDBALAdapter
     */
    private $adapter;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = StorageAdapterFactory::inMemory();

        wait(
            $this->adapter->execute(
                'CREATE TABLE IF NOT EXISTS test_result_set (id varchar PRIMARY KEY, value VARCHAR)'
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

        wait(
            $this->adapter->execute('DROP TABLE test_result_set')
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function fetchOne(): void
    {
        $handler = static function(DoctrineDBALResultSetTest $self): \Generator
        {
            yield $self->adapter->execute(
                'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                    'uuid1', 'value1',
                    'uuid2', 'value2'
                ]
            );

            $result = yield fetchOne(
                yield $self->adapter->execute('SELECT * FROM test_result_set WHERE id = \'uuid2\'')
            );

            static::assertNotEmpty($result);
            static:: assertEquals(['id' => 'uuid2', 'value' => 'value2'], $result);

            $result = yield fetchOne(
                yield $self->adapter->execute('SELECT * FROM test_result_set WHERE id = \'uuid4\'')
            );

            static::assertNull($result);
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
    public function fetchAll(): void
    {
        $handler = static function(DoctrineDBALResultSetTest $self): \Generator
        {
            yield $self->adapter->execute(
                'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                    'uuid1', 'value1',
                    'uuid2', 'value2'
                ]
            );

            $result = yield fetchAll(
                yield $self->adapter->execute('SELECT * FROM test_result_set')
            );

            static::assertNotEmpty($result);
            static::assertCount(2, $result);
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
    public function fetchAllWithEmptySet(): void
    {
        $handler = static function(DoctrineDBALResultSetTest $self): \Generator
        {
            $result = yield fetchAll(
                yield $self->adapter->execute('SELECT * FROM test_result_set')
            );

            static::assertInternalType('array', $result);
            static::assertEmpty($result);
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
    public function multipleGetCurrentRow(): void
    {
        $handler = static function(DoctrineDBALResultSetTest $self): \Generator
        {
            yield $self->adapter->execute(
                'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                    'uuid1', 'value1',
                    'uuid2', 'value2'
                ]
            );

            /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
            $result = yield $self->adapter->execute('SELECT * FROM test_result_set');

            while(yield $result->advance())
            {
                $row = $result->getCurrent();
                $rowCopy = $result->getCurrent();

                static::assertEquals($row, $rowCopy);
            }
        };

        wait(new Coroutine($handler($this)));
    }
}
