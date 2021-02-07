<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Services\Attributes;

use PHPUnit\Framework\TestCase;
use ServiceBus\Services\Attributes\EventListener;

/**
 *
 */
final class EventListenerTest extends TestCase
{
    /**
     * @test
     */
    public function withoutAnyFields(): void
    {
        $attribute = new EventListener();

        self::assertNull($attribute->description());
        self::assertNull($attribute->validation());
    }

    /**
     * @test
     */
    public function withValidation(): void
    {
        $attribute = new EventListener(
            description: 'qwerty',
            validationEnabled: true,
            validationGroups: ['qwerty'],
        );

        self::assertSame('qwerty', $attribute->description());
        self::assertSame(['qwerty'], $attribute->validation()?->groups);
    }
}
