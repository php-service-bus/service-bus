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

namespace Desperado\Framework\Tests\Domain;

use Desperado\Framework\Domain\DateTime;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DateTimeTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function nowDatetime(): void
    {
        static::assertEquals(\date('Y:m:d'), DateTime::nowToString('Y:m:d'));
        static::assertEquals(
            \date('Y:m:d H:i'),
            (DateTime::now('Europe/Minsk'))->toString('Y:m:d H:i')
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function fromString(): void
    {
        static::assertEquals(
            '2017:01:02 00:00:00',
            (DateTime::fromString('2017:01:02 00:00:00'))->toString('Y:m:d H:i:s')
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function fromFormat(): void
    {
        static::assertEquals(
            '2017-01-02011539',
            (DateTime::fromFormat('Y:m:d H:i:s', '2017:01:02 01:15:39'))->toString('Y-m-dHis')
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function dateTimeToString(): void
    {
        $expectedDateTime = '2017:01:02 00:00:00';
        $datetime = DateTime::fromString($expectedDateTime, 'Europe/London');

        static::assertEquals($expectedDateTime, $datetime->toString('Y:m:d H:i:s'));
        static::assertEquals('2017-01-02T00:00:00.000000+00:00', (string) $datetime);
    }
}
