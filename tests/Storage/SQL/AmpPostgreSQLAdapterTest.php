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
    public static function tearDownAfterClass(): void
    {
        $adapter = static::getAdapter();

        wait($adapter->execute('DROP TABLE storage_test_table'));
    }

    /**
     * @inheritdoc
     */
    protected static function getAdapter(): StorageAdapter
    {
        $connectionDSN = (string) \getenv('TEST_POSTGRES_DSN');

        if('' === $connectionDSN)
        {
            $connectionDSN = 'pgsql://postgres:123456789@localhost:5432/test';
        }

        return StorageAdapterFactory::create(
            StorageAdapterFactory::ADAPTER_ASYNC_POSTGRES,
            $connectionDSN
        );
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
