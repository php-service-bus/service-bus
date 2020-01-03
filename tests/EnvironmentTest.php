<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests;

use PHPUnit\Framework\TestCase;
use ServiceBus\Environment;

/**
 *
 */
final class EnvironmentTest extends TestCase
{
    /** @test */
    public function wrongEnvironment(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Provided incorrect value of the environment: "qwerty". Allowable values: prod, dev, test');

        Environment::create('qwerty');
    }

    /** @test */
    public function createTestEnv(): void
    {
        $environment = Environment::test();

        static::assertSame('test', $environment->toString());
        static::assertTrue($environment->equals(Environment::create('test')));
        static::assertTrue($environment->isTesting());
        static::assertTrue($environment->isDebug());
        static::assertFalse($environment->isProduction());
        static::assertFalse($environment->isDevelopment());
    }

    /** @test */
    public function createDevEnv(): void
    {
        $environment = Environment::dev();

        static::assertSame('dev', $environment->toString());
        static::assertTrue($environment->equals(Environment::create('dev')));
        static::assertTrue($environment->isDevelopment());
        static::assertTrue($environment->isDebug());
        static::assertFalse($environment->isTesting());
        static::assertFalse($environment->isProduction());
    }

    /** @test */
    public function createProdEnv(): void
    {
        $environment = Environment::prod();

        static::assertSame('prod', $environment->toString());
        static::assertTrue($environment->equals(Environment::create('prod')));
        static::assertTrue($environment->isProduction());
        static::assertFalse($environment->isDevelopment());
        static::assertFalse($environment->isDebug());
        static::assertFalse($environment->isTesting());
    }
}
