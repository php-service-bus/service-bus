<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Services\Annotations;

use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use PHPUnit\Framework\TestCase;

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
