<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageHandlers;

use PHPUnit\Framework\TestCase;
use ServiceBus\MessageHandlers\HandlerOptions;
use ServiceBus\Tests\Stubs\Messages\ExecutionFailed;
use ServiceBus\Tests\Stubs\Messages\ValidationFailed;

/**
 *
 */
final class HandlerOptionsTest extends TestCase
{
    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage Event class "ServiceBus\Tests\MessageHandlers\HandlerOptionsTest" must
     *                           implement "Desperado\ServiceBus\Services\Contracts\ExecutionFailedEvent" interface
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createCommandHandlerWithWrongThrowableEvent(): void
    {
        HandlerOptions::create()->useDefaultThrowableEvent(__CLASS__);
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage Event class "ServiceBus\Tests\MessageHandlers\HandlerOptionsTest" must
     *                           implement "Desperado\ServiceBus\Services\Contracts\ValidationFailedEvent" interface
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createCommandHandlerWithWrongValidationFailureEvent(): void
    {
        HandlerOptions::create()->useDefaultValidationFailedEvent(__CLASS__);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createCommandHandlerWithThrowableEvent(): void
    {
        $options = HandlerOptions::create();
        $options->useDefaultThrowableEvent(ExecutionFailed::class);

        static::assertNotNull($options->defaultThrowableEvent);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createCommandHandlerWithValidationFailureEvent(): void
    {
        $options = HandlerOptions::create();
        $options->useDefaultValidationFailedEvent(ValidationFailed::class);

        static::assertNotNull($options->defaultValidationFailedEvent);
    }
}
