<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Tests\Domain\Environment;

use Desperado\ConcurrencyFramework\Domain\Environment\Environment;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class EnvironmentTest extends TestCase
{
    /**
     * @test
     * @expectedException  \Desperado\ConcurrencyFramework\Domain\Environment\Exceptions\InvalidEnvironmentException
     * @expectedExceptionMessage Wrong environment specified (""). Expected choices: prod, dev, test
     *
     * @return void
     */
    public function emptyEnvironment(): void
    {
        new Environment('');
    }

    /**
     * @test
     * @expectedException  \Desperado\ConcurrencyFramework\Domain\Environment\Exceptions\InvalidEnvironmentException
     * @expectedExceptionMessage Wrong environment specified ("qwerty"). Expected choices: prod, dev, test
     *
     * @return void
     */
    public function wrongEnvironment(): void
    {
        new Environment('qwerty');
    }

    /**
     * @test
     *
     * @return void
     */
    public function isDebug(): void
    {
        static::assertTrue((new Environment('dev'))->isDebug());
        static::assertTrue((new Environment('test'))->isDebug());
        static::assertFalse((new Environment('prod'))->isDebug());
    }

    /**
     * @test
     *
     * @return void
     */
    public function toStringEnvironment(): void
    {
        static::assertEquals('prod', new Environment('prod'));
    }
}
