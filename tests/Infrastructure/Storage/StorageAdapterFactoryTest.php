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

use Desperado\ServiceBus\Infrastructure\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class StorageAdapterFactoryTest extends TestCase
{
    /**
     * @test
     * @expectedException  \LogicException
     * @expectedExceptionMessage Invalid adapter specified ("qwerty")
     *
     * @return void
     */
    public function createWithUnknownAdapter(): void
    {
        StorageAdapterFactory::create('qwerty', 'dsn');
    }

    /**
     * @test
     *
     * @return void
     */
    public static function inMemory(): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(
            DoctrineDBALAdapter::class,
            StorageAdapterFactory::inMemory()
        );
    }
}
