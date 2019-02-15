<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests;

use PHPUnit\Framework\TestCase;
use function ServiceBus\formatBytes;

/**
 *
 */
final class FunctionsTest extends TestCase
{
    /**
     * @test
     * @dataProvider formatBytesDataProvider
     *
     * @param int    $bytes
     * @param string $expected
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function formatBytes(int $bytes, string $expected): void
    {
        static::assertSame($expected, formatBytes($bytes));
    }

    /**
     * @return array
     */
    public function formatBytesDataProvider(): array
    {
        return [
            [1, '1 b'],
            [10000, '9.77 kb'],
            [10000000, '9.54 mb']
        ];
    }
}
