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
use function Amp\Promise\wait;
use Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Storage\StorageConfiguration;

/**
 *
 */
final class AmpPostgreSQLAdapterTest extends BaseStorageAdapterTest
{
    /**
     * @return void
     *
     * @throws \Throwable
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        wait(
            static::getAdapter()->execute(
                'CREATE TABLE IF NOT EXISTS test_ai (id serial PRIMARY KEY, value VARCHAR)'
            )
        );
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public static function tearDownAfterClass(): void
    {
        $adapter = static::getAdapter();

        wait($adapter->execute('DROP TABLE storage_test_table'));
        wait($adapter->execute('DROP TABLE test_ai'));
    }

    /**
     * @inheritdoc
     */
    protected static function getAdapter(): StorageAdapter
    {
        return StorageAdapterFactory::create(
            AmpPostgreSQLAdapter::class,
            (string) \getenv('TEST_POSTGRES_DSN')
        );
    }

    /**
     * @test
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function lastInsertId(): void
    {
        $handler = static function(AmpPostgreSQLAdapterTest $self): \Generator
        {
            try
            {
                $adapter = static::getAdapter();

                /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
                $result = yield $adapter->execute('INSERT INTO test_ai (value) VALUES (\'qwerty\') RETURNING id');

                static::assertEquals('1', $result->lastInsertId());

                /** @var \Desperado\ServiceBus\Storage\ResultSet $result */
                $result = yield $adapter->execute('INSERT INTO test_ai (value) VALUES (\'qwerty\') RETURNING id');

                static::assertEquals('2', $result->lastInsertId());
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
     * @expectedException \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function failedConnection(): void
    {
        $adapter = new AmpPostgreSQLAdapter(
            StorageConfiguration::fromDSN('qwerty')
        );

        wait($adapter->execute('SELECT now()'));
    }
}
