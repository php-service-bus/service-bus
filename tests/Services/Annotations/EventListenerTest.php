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
use ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class EventListenerTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function withWrongProperties(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EventListener(['qwerty' => 'root']);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function withoutAnyFields(): void
    {
        $annotation = new EventListener([]);

        static::assertFalse($annotation->validate);
        static::assertEmpty($annotation->groups);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function withValidation(): void
    {
        $annotation = new EventListener([
            'validate' => true,
            'groups'   => [
                'qwerty',
                'root',
            ],
        ]);

        static::assertTrue($annotation->validate);
        static::assertSame(['qwerty', 'root'], $annotation->groups);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function withCustomEvents(): void
    {
        $handler = new EventListener(
            [
                'defaultValidationFailedEvent' => EventListener::class,
                'defaultThrowableEvent'        => \Throwable::class,
            ]
        );

        self::assertSame(EventListener::class, $handler->defaultValidationFailedEvent);
        self::assertSame(\Throwable::class, $handler->defaultThrowableEvent);
    }
}
