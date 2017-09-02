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

namespace Desperado\ConcurrencyFramework\Tests\Common\Formatter;

use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ThrowableFormatterTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function simpleException(): void
    {
        $formatted = ThrowableFormatter::toString(new \Exception());

        $expected = \sprintf('[object] (Exception(code: 0):  at %s:31)', __FILE__);

        static::assertEquals($expected, $formatted);
    }

    /**
     * @test
     *
     * @return void
     */
    public function withPrevious(): void
    {
        $formatted = ThrowableFormatter::toString(
            new \Exception('message', 1, new \LogicException('Previous'))
        );

        $expected = \sprintf(
            '[object] (Exception(code: 1): message at %s:46, LogicException(code: 0 Previous at %s:46)',
            __FILE__, __FILE__
        );

        static::assertEquals($expected, $formatted);
    }

    /**
     * @test
     *
     * @return void
     */
    public function withMultiplyPrevious(): void
    {
        $formatted = ThrowableFormatter::toString(
            new \Exception('message', 1, new \LogicException('Previous', 0, new \RuntimeException('abube')))
        );

        $expected = \sprintf(
            '[object] (Exception(code: 1): message at %s:65, LogicException(code: 0 Previous at %s:65, RuntimeException(code: 0 abube at %s:65)',
            __FILE__, __FILE__, __FILE__
        );

        static::assertEquals($expected, $formatted);
    }
}
