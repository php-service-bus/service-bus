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

namespace Desperado\ServiceBus\Tests\Infrastructure\Storage\SQL\AmpPostgreSQL;

use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\uuid;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchAll;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageConfiguration;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmpPostgreSQLResultSetTest extends TestCase
{
    /**
     * @var AmpPostgreSQLAdapter
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

        $this->adapter = new AmpPostgreSQLAdapter(
            StorageConfiguration::fromDSN((string) \getenv('TEST_POSTGRES_DSN'))
        );

        wait(
            $this->adapter->execute(
                'CREATE TABLE IF NOT EXISTS test_result_set (id uuid PRIMARY KEY, value VARCHAR)'
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
        $uuid1 = uuid();
        $uuid2 = uuid();

        $promise = $this->adapter->execute(
            'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                $uuid1, 'value1',
                $uuid2, 'value2'
            ]
        );

        wait($promise);

        $result = wait(
            fetchOne(
                wait($this->adapter->execute(\sprintf('SELECT * FROM test_result_set WHERE id = \'%s\'', $uuid2)))
            )
        );

        static::assertNotEmpty($result);
        static:: assertEquals(['id' => $uuid2, 'value' => 'value2'], $result);

        $result = wait(
            fetchOne(
                wait(
                    $this->adapter->execute('SELECT * FROM test_result_set WHERE id = \'b4141f6e-a461-11e8-98d0-529269fb1459\'')
                )
            )
        );

        static::assertNull($result);
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
        $promise = $this->adapter->execute(
            'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                uuid(), 'value1',
                uuid(), 'value2'
            ]
        );

        wait($promise);

        $result = wait(
            fetchAll(
                wait($this->adapter->execute('SELECT * FROM test_result_set'))
            )
        );

        static::assertNotEmpty($result);
        static::assertCount(2, $result);
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
        $result = wait(
            fetchAll(
                wait($this->adapter->execute('SELECT * FROM test_result_set'))
            )
        );

        static::assertThat($result, new IsType('array'));
        static::assertEmpty($result);
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
        $promise = $this->adapter->execute(
            'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                uuid(), 'value1',
                uuid(), 'value2'
            ]
        );

        wait($promise);

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $result */
        $result = wait($this->adapter->execute('SELECT * FROM test_result_set'));

        while(wait($result->advance()))
        {
            $row     = $result->getCurrent();
            $rowCopy = $result->getCurrent();

            static::assertEquals($row, $rowCopy);
        }
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function executeCommand(): void
    {
        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $result */
        $result = wait($this->adapter->execute('DELETE FROM test_result_set'));

        while(wait($result->advance()))
        {
            static::fail('Non empty cycle');
        }

        static::assertNull($result->lastInsertId());
    }
}
