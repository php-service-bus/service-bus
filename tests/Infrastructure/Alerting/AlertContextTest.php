<?php

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
    /** @test */
    public function createDefault(): void
    {
        $context = new AlertContext();

        static::assertFalse($context->toDrawAttention);
        static::assertNull($context->toTopic);
    }

    /** @test */
    public function createCustom(): void
    {
        $context = new AlertContext(true, 'topic');

        static::assertTrue($context->toDrawAttention);
        static::assertSame('topic', $context->toTopic);
    }
}
