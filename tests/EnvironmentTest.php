<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
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
    /**
     * @test
     */
    public function wrongEnvironment(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Provided incorrect value of the environment: "qwerty". Allowable values: prod, dev, test');

        Environment::create('qwerty');
    }

    /**
     * @test
     */
    public function createTestEnv(): void
    {
        $environment = Environment::test();

        self::assertSame('test', $environment->toString());
        self::assertTrue($environment->equals(Environment::create('test')));
        self::assertTrue($environment->isTesting());
        self::assertTrue($environment->isDebug());
        self::assertFalse($environment->isProduction());
        self::assertFalse($environment->isDevelopment());
    }

    /**
     * @test
     */
    public function createDevEnv(): void
    {
        $environment = Environment::dev();

        self::assertSame('dev', $environment->toString());
        self::assertTrue($environment->equals(Environment::create('dev')));
        self::assertTrue($environment->isDevelopment());
        self::assertTrue($environment->isDebug());
        self::assertFalse($environment->isTesting());
        self::assertFalse($environment->isProduction());
    }

    /** @test */
    public function createProdEnv(): void
    {
        $environment = Environment::prod();

        self::assertSame('prod', $environment->toString());
        self::assertTrue($environment->equals(Environment::create('prod')));
        self::assertTrue($environment->isProduction());
        self::assertFalse($environment->isDevelopment());
        self::assertFalse($environment->isDebug());
        self::assertFalse($environment->isTesting());
    }
}
