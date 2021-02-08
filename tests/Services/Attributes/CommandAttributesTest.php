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
use ServiceBus\Services\Attributes\CommandHandler;

/**
 *
 */
final class CommandAttributesTest extends TestCase
{
    /**
     * @test
     */
    public function withoutAnyFields(): void
    {
        $attribute = new CommandHandler();

        self::assertNull($attribute->description());
        self::assertNull($attribute->validation());
        self::assertNotNull($attribute->cancellation());
    }

    /**
     * @test
     */
    public function withValidation(): void
    {
        $attribute = new CommandHandler(
            validationEnabled: true,
            validationGroups: ['qwerty', 'root']
        );

        self::assertNull($attribute->description());
        self::assertNotNull($attribute->cancellation());
        self::assertSame(['qwerty', 'root'], $attribute->validation()?->groups);
    }

    /**
     * @test
     */
    public function withCancellation(): void
    {
        $attribute = new CommandHandler(
            description: 'qwerty',
            validationEnabled: true,
            validationGroups: ['qwerty', 'root'],
            executionTimeout: 5
        );

        self::assertSame('qwerty', $attribute->description());
        self::assertSame(5, $attribute->cancellation()->timeout);
        self::assertSame(['qwerty', 'root'], $attribute->validation()?->groups);
    }
}
