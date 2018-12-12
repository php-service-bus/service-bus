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

namespace Desperado\ServiceBus\Tests\Infrastructure\Storage;

use Desperado\ServiceBus\Infrastructure\Storage\StorageConfiguration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class StorageConfigurationTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function parseSqlite(): void
    {
        $configuration = StorageConfiguration::fromDSN('sqlite:///:memory:');

        static::assertEquals('sqlite:///:memory:', $configuration->originalDSN);
        static::assertEquals('sqlite', $configuration->scheme);
        static::assertEquals('localhost', $configuration->host);
        static::assertEquals(':memory:', $configuration->databaseName);
        static::assertEquals('UTF-8', $configuration->encoding);
        static::assertFalse($configuration->hasCredentials());
    }

    /**
     * @test
     *
     * @return void
     */
    public function parseFullDSN(): void
    {
        $configuration = StorageConfiguration::fromDSN(
            'pgsql://someUser:someUserPassword@host:54332/databaseName?charset=UTF-16'
        );
        static::assertEquals('pgsql', $configuration->scheme);
        static::assertEquals('host', $configuration->host);
        static::assertEquals(54332, $configuration->port);
        static::assertEquals('databaseName', $configuration->databaseName);
        static::assertEquals('UTF-16', $configuration->encoding);
        static::assertTrue($configuration->hasCredentials());
        static::assertEquals('someUser', $configuration->username);
        static::assertEquals('someUserPassword', $configuration->password);
    }
}
