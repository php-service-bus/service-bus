<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
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
    /**
     * @test
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function withWrongProperties(): void
    {
        new CommandHandler(['qwerty' => 'root']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withoutAnyFields(): void
    {
        $annotation = new CommandHandler([]);

        static::assertFalse($annotation->validate);
        static::assertEmpty($annotation->groups);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withValidation(): void
    {
        $annotation = new CommandHandler([
            'validate' => true,
            'groups'   => [
                'qwerty',
                'root'
            ]
        ]);

        static::assertTrue($annotation->validate);
        static::assertEquals(['qwerty', 'root'], $annotation->groups);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withCustomEvents(): void
    {
        $handler = new CommandHandler([
                'defaultValidationFailedEvent' => CommandHandler::class,
                'defaultThrowableEvent'        => \Throwable::class
            ]
        );

        self::assertEquals(CommandHandler::class, $handler->defaultValidationFailedEvent);
        self::assertEquals(\Throwable::class, $handler->defaultThrowableEvent);
    }
}
