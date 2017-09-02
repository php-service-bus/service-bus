<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Tests\Domain;

use Desperado\ConcurrencyFramework\Domain\Uuid;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class UuidTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function new(): void
    {
        $uuid = Uuid::new();

        static::assertNotEmpty($uuid);
        static::assertTrue(Uuid::isValid($uuid));
    }

    /**
     * @test
     *
     * @return void
     */
    public function isValid(): void
    {
        static::assertFalse(Uuid::isValid('someString'));
    }
}
