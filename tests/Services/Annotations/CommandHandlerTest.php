<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Services\Annotations;

use PHPUnit\Framework\TestCase;
use ServiceBus\Services\Annotations\CommandHandler;

/**
 *
 */
final class CommandHandlerTest extends TestCase
{
    /** @test */
    public function withWrongProperties(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CommandHandler(['qwerty' => 'root']);
    }

    /** @test */
    public function withoutAnyFields(): void
    {
        $annotation = new CommandHandler([]);

        static::assertFalse($annotation->validate);
        static::assertEmpty($annotation->groups);
    }

    /** @test */
    public function withValidation(): void
    {
        $annotation = new CommandHandler([
            'validate' => true,
            'groups'   => [
                'qwerty',
                'root',
            ],
        ]);

        static::assertTrue($annotation->validate);
        static::assertSame(['qwerty', 'root'], $annotation->groups);
    }

    /** @test */
    public function withCustomEvents(): void
    {
        $handler = new CommandHandler(
            [
                'defaultValidationFailedEvent' => CommandHandler::class,
                'defaultThrowableEvent'        => \Throwable::class,
            ]
        );

        self::assertSame(CommandHandler::class, $handler->defaultValidationFailedEvent);
        self::assertSame(\Throwable::class, $handler->defaultThrowableEvent);
    }
}