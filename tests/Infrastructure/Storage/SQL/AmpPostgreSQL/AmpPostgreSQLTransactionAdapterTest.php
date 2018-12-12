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
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use Desperado\ServiceBus\Infrastructure\Storage\StorageConfiguration;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmpPostgreSQLTransactionAdapterTest extends TestCase
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
    public function simpleTransaction(): void
    {
        /** @var \Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter $transaction */
        $transaction = wait($this->adapter->transaction());

        wait(
            $this->adapter->execute(
                'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                    uuid(), 'value1',
                    uuid(), 'value2'
                ]
            )
        );

        wait($transaction->commit());

        /** check results */

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
    public function transactionWithReadData(): void
    {
        $uuid = uuid();

        $query = insertQuery('test_result_set', ['id' => $uuid, 'value' => 'value2'])->compile();

        wait($this->adapter->execute($query->sql(), $query->params()));

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter $transaction */
        $transaction = wait($this->adapter->transaction());

        $query = selectQuery('test_result_set')
            ->where(equalsCriteria('id', $uuid))
            ->compile();

        $someReadData = wait(fetchOne(wait($transaction->execute($query->sql(), $query->params()))));

        static::assertNotEmpty($someReadData);
        static::assertCount(2, $someReadData);

        wait($transaction->commit());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function rollback(): void
    {
        /** @var \Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter $transaction */
        $transaction = wait($this->adapter->transaction());

        $query = insertQuery('test_result_set', ['id' => uuid(), 'value' => 'value2'])->compile();

        wait($transaction->execute($query->sql(), $query->params()));
        wait($transaction->rollback());

        $query = selectQuery('test_result_set')->compile();

        /** @var array $collection */
        $collection = wait(fetchAll(wait($this->adapter->execute($query->sql(), $query->params()))));

        static::assertThat($collection, new IsType('array'));
        static::assertCount(0, $collection);
    }
}
