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
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Infrastructure\Storage\StorageConfiguration;
use Desperado\ServiceBus\Tests\Infrastructure\Storage\SQL\BaseStorageAdapterTest;

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
     * @return void
     *
     * @throws \Throwable
     */
    public function lastInsertId(): void
    {
        $adapter = static::getAdapter();

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $result */
        $result = wait($adapter->execute('INSERT INTO test_ai (value) VALUES (\'qwerty\') RETURNING id'));

        static::assertEquals('1', $result->lastInsertId());

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $result */
        $result = wait($adapter->execute('INSERT INTO test_ai (value) VALUES (\'qwerty\') RETURNING id'));

        static::assertEquals('2', $result->lastInsertId());
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
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
