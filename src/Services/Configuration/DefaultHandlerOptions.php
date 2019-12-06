<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Configuration;

use ServiceBus\Common\MessageExecutor\MessageHandlerOptions;
use ServiceBus\Services\Contracts\ExecutionFailedEvent;
use ServiceBus\Services\Contracts\ValidationFailedEvent;
use ServiceBus\Services\Exceptions\InvalidEventType;

/**
 * Execution options.
 */
final class DefaultHandlerOptions implements MessageHandlerOptions
{
    /**
     * Is this an event listener?
     *
     * @var bool
     */
    public $isEventListener;

    /**
     * Is this a command handler?
     *
     * @var bool
     */
    public $isCommandHandler;

    /**
     * Validation enabled.
     *
     * @var bool
     */
    public $validationEnabled = false;

    /**
     * Validation groups.
     *
     * @psalm-var array<array-key, string>
     *
     * @var array
     */
    public $validationGroups = [];

    /**
     * In case of validation errors, automatically send the event and stop further execution
     * The event must implement @see ValidationFailedEvent interface.
     *
     * If no class is specified, control is passed to user code
     *
     * @psalm-var class-string|null
     *
     * @var string|null
     */
    public $defaultValidationFailedEvent = null;

    /**
     * In case of a runtime error, automatically send the specified event with the message received from the exception
     * The event must implement @see ExecutionFailedEvent interface.
     *
     * If no class is specified, control is passed to user code
     *
     * @psalm-var class-string|null
     *
     * @var string|null
     */
    public $defaultThrowableEvent = null;

    public static function createForEventListener(): self
    {
        return new self(true, false);
    }

    public static function createForCommandHandler(): self
    {
        return new self(false, true);
    }

    /**
     * Enable validation.
     *
     * @psalm-param array<array-key, string> $validationGroups
     */
    public function enableValidation(array $validationGroups = []): self
    {
        $defaultValidationFailedEvent = $this->defaultValidationFailedEvent;
        $defaultThrowableEvent        = $this->defaultThrowableEvent;

        /**
         * @psalm-var class-string|null $defaultValidationFailedEvent
         * @psalm-var class-string|null $defaultThrowableEvent
         */

        return new self(
            $this->isEventListener,
            $this->isCommandHandler,
            true,
            $validationGroups,
            $defaultValidationFailedEvent,
            $defaultThrowableEvent
        );
    }

    /**
     * @psalm-param class-string $eventClass
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType Event class must implement @see ExecutionFailedEvent
     */
    public function withDefaultValidationFailedEvent(string $eventClass): self
    {
        if (false === \is_a($eventClass, ValidationFailedEvent::class, true))
        {
            throw new InvalidEventType(
                \sprintf(
                    'Event class "%s" must implement "%s" interface',
                    $eventClass,
                    ValidationFailedEvent::class
                )
            );
        }

        $defaultThrowableEvent = $this->defaultThrowableEvent;

        /**
         * @psalm-var class-string $eventClass
         * @psalm-var class-string|null $defaultThrowableEvent
         */

        return new self(
            $this->isEventListener,
            $this->isCommandHandler,
            $this->validationEnabled,
            $this->validationGroups,
            $eventClass,
            $defaultThrowableEvent
        );
    }

    /**
     * @psalm-param class-string $eventClass
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType Event class must implement @see ExecutionFailedEvent
     */
    public function withDefaultThrowableEvent(string $eventClass): self
    {
        if (false === \is_a($eventClass, ExecutionFailedEvent::class, true))
        {
            throw new InvalidEventType(
                \sprintf(
                    'Event class "%s" must implement "%s" interface',
                    $eventClass,
                    ExecutionFailedEvent::class
                )
            );
        }

        $defaultValidationFailedEvent = $this->defaultValidationFailedEvent;

        /**
         * @psalm-var class-string $eventClass
         * @psalm-var class-string|null $defaultValidationFailedEvent
         */

        return new self(
            $this->isEventListener,
            $this->isCommandHandler,
            $this->validationEnabled,
            $this->validationGroups,
            $defaultValidationFailedEvent,
            $eventClass
        );
    }

    /**
     * @psalm-param  array<array-key, string> $validationGroups
     * @psalm-param  class-string|null $defaultValidationFailedEvent
     * @psalm-param  class-string|null $defaultThrowableEvent
     */
    private function __construct(
        bool $isEventListener,
        bool $isCommandHandler,
        bool $validationEnabled = false,
        array $validationGroups = [],
        ?string $defaultValidationFailedEvent = null,
        ?string $defaultThrowableEvent = null
    ) {
        $this->isEventListener              = $isEventListener;
        $this->isCommandHandler             = $isCommandHandler;
        $this->validationEnabled            = $validationEnabled;
        $this->validationGroups             = $validationGroups;
        $this->defaultValidationFailedEvent = $defaultValidationFailedEvent;
        $this->defaultThrowableEvent        = $defaultThrowableEvent;
    }
}
