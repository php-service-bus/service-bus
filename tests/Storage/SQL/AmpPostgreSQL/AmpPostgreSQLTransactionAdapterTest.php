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

namespace Desperado\ServiceBus\Tests\Storage\SQL\AmpPostgreSQL;

use Amp\Coroutine;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\uuid;
use function Desperado\ServiceBus\Storage\fetchAll;
use function Desperado\ServiceBus\Storage\fetchOne;
use Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use function Desperado\ServiceBus\Storage\SQL\createInsertQuery;
use function Desperado\ServiceBus\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Storage\SQL\selectQuery;
use Desperado\ServiceBus\Storage\StorageConfiguration;
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
        $handler = static function(AmpPostgreSQLTransactionAdapterTest $self): \Generator
        {
            /** @var \Desperado\ServiceBus\Storage\TransactionAdapter $transaction */
            $transaction = yield $self->adapter->transaction();

            try
            {
                yield $self->adapter->execute(
                    'INSERT INTO test_result_set (id, value) VALUES (?,?), (?,?)', [
                        uuid(), 'value1',
                        uuid(), 'value2'
                    ]
                );

                yield $transaction->commit();

                /** check results */

                $result = yield fetchAll(
                    yield $self->adapter->execute('SELECT * FROM test_result_set')
                );

                static::assertNotEmpty($result);
                static::assertCount(2, $result);
            }
            catch(\Throwable $throwable)
            {
                yield $transaction->rollback();

                static::fail($throwable->getMessage());
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
    public function transactionWithReadData(): void
    {
        $handler = static function(AmpPostgreSQLTransactionAdapterTest $self): \Generator
        {
            $uuid = uuid();

            $query = createInsertQuery('test_result_set', ['id' => $uuid, 'value' => 'value2'])->compile();

            yield $self->adapter->execute($query->sql(), $query->params());

            /** @var \Desperado\ServiceBus\Storage\TransactionAdapter $transaction */
            $transaction = yield $self->adapter->transaction();

            try
            {
                $query = selectQuery('test_result_set')
                    ->where(equalsCriteria('id', $uuid))
                    ->compile();

                $someReadData = yield fetchOne(yield $transaction->execute($query->sql(), $query->params()));

                static::assertNotEmpty($someReadData);
                static::assertCount(2, $someReadData);

                yield $transaction->commit();
            }
            catch(\Throwable $throwable)
            {
                yield $transaction->rollback();

                static::fail($throwable->getMessage());
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
    public function rollback(): void
    {
        $handler = static function(AmpPostgreSQLTransactionAdapterTest $self): \Generator
        {
            /** @var \Desperado\ServiceBus\Storage\TransactionAdapter $transaction */
            $transaction = yield $self->adapter->transaction();

            $query = createInsertQuery('test_result_set', ['id' => uuid(), 'value' => 'value2'])->compile();

            yield $transaction->execute($query->sql(), $query->params());
            yield $transaction->rollback();

            $query = selectQuery('test_result_set')->compile();

            /** @var array $collection */
            $collection = yield fetchAll(yield $self->adapter->execute($query->sql(), $query->params()));

            static::assertInternalType('array', $collection);
            static::assertCount(0, $collection);
        };

        wait(new Coroutine($handler($this)));
    }
}
