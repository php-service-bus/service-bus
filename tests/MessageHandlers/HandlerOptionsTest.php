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

namespace Desperado\ServiceBus\Tests\MessageHandlers;

use Desperado\ServiceBus\MessageHandlers\HandlerOptions;
use Desperado\ServiceBus\Tests\Stubs\Messages\ExecutionFailed;
use Desperado\ServiceBus\Tests\Stubs\Messages\ValidationFailed;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class HandlerOptionsTest extends TestCase
{
    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage Event class "Desperado\ServiceBus\Tests\MessageHandlers\HandlerOptionsTest" must
     *                           implement "Desperado\ServiceBus\Services\Contracts\ExecutionFailedEvent" interface
     *
     * @return void
     */
    public function createCommandHandlerWithWrongThrowableEvent(): void
    {
        (new HandlerOptions())->useDefaultThrowableEvent(__CLASS__);
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage Event class "Desperado\ServiceBus\Tests\MessageHandlers\HandlerOptionsTest" must
     *                           implement "Desperado\ServiceBus\Services\Contracts\ValidationFailedEvent" interface
     *
     * @return void
     */
    public function createCommandHandlerWithWrongValidationFailureEvent(): void
    {
        (new HandlerOptions())->useDefaultValidationFailedEvent(__CLASS__);
    }

    /**
     * @test
     *
     * @return void
     */
    public function createCommandHandlerWithThrowableEvent(): void
    {
        $options = new HandlerOptions();
        $options->useDefaultThrowableEvent(ExecutionFailed::class);

        static::assertTrue($options->hasDefaultThrowableEvent());
    }

    /**
     * @test
     *
     * @return void
     */
    public function createCommandHandlerWithValidationFailureEvent(): void
    {
        $options = new HandlerOptions();
        $options->useDefaultValidationFailedEvent(ValidationFailed::class);

        static::assertTrue($options->hasDefaultValidationFailedEvent());
    }
}
