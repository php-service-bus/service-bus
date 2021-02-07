<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Alerting;

use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Alerting\AlertContext;

/**
 *
 */
final class AlertContextTest extends TestCase
{
    /**
     * @test
     */
    public function createDefault(): void
    {
        $context = new AlertContext();

        self::assertFalse($context->toDrawAttention);
        self::assertNull($context->toTopic);
    }

    /**
     * @test
     */
    public function createCustom(): void
    {
        $context = new AlertContext(true, 'topic');

        self::assertTrue($context->toDrawAttention);
        self::assertSame('topic', $context->toTopic);
    }
}
