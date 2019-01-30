<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
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
 * Execution options
 *
 * @property-read bool               $isEventListener
 * @property-read bool               $isCommandHandler
 * @property-read bool               $validationEnabled
 * @property-read array<int, string> $validationGroups
 * @property-read string|null        $defaultValidationFailedEvent
 * @property-read string|null        $defaultThrowableEvent
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
     * Validation enabled
     *
     * @var bool
     */
    public $validationEnabled = false;

    /**
     * Validation groups
     *
     * @var array<int, string>
     */
    public $validationGroups = [];

    /**
     * In case of validation errors, automatically send the event and stop further execution
     * The event must implement @see ValidationFailedEvent interface
     *
     * If no class is specified, control is passed to user code
     *
     * @var string|null
     */
    public $defaultValidationFailedEvent;

    /**
     * In case of a runtime error, automatically send the specified event with the message received from the exception
     * The event must implement @see ExecutionFailedEvent interface
     *
     * If no class is specified, control is passed to user code
     *
     * @var string|null
     */
    public $defaultThrowableEvent;

    /**
     * @return self
     */
    public static function createForEventListener(): self
    {
        return new self(true, false);
    }

    /**
     * @return self
     */
    public static function createForCommandHandler(): self
    {
        return new self(false, true);
    }

    /**
     * Enable validation
     *
     * @param array<int, string> $validationGroups
     *
     * @return self
     */
    public function enableValidation(array $validationGroups = []): self
    {
        return new self(
            $this->isEventListener,
            $this->isCommandHandler,
            true,
            $validationGroups,
            $this->defaultValidationFailedEvent,
            $this->defaultThrowableEvent
        );
    }

    /**
     * @param string $eventClass
     *
     * @return self
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType Event class must implement @see ExecutionFailedEvent
     */
    public function withDefaultValidationFailedEvent(string $eventClass): self
    {
        if(false === \is_a($eventClass, ValidationFailedEvent::class, true))
        {
            throw new InvalidEventType(
                \sprintf(
                    'Event class "%s" must implement "%s" interface', $eventClass,
                    ValidationFailedEvent::class
                )
            );
        }

        return new self(
            $this->isEventListener,
            $this->isCommandHandler,
            $this->validationEnabled,
            $this->validationGroups,
            $eventClass,
            $this->defaultThrowableEvent
        );
    }

    /**
     * @param string $eventClass
     *
     * @return self
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType Event class must implement @see ExecutionFailedEvent
     */
    public function withDefaultThrowableEvent(string $eventClass): self
    {
        if(false === \is_a($eventClass, ExecutionFailedEvent::class, true))
        {
            throw new InvalidEventType(
                \sprintf(
                    'Event class "%s" must implement "%s" interface', $eventClass,
                    ExecutionFailedEvent::class
                )
            );
        }

        return new self(
            $this->isEventListener,
            $this->isCommandHandler,
            $this->validationEnabled,
            $this->validationGroups,
            $this->defaultValidationFailedEvent,
            $eventClass
        );
    }

    /**
     * @param bool        $isEventListener
     * @param bool        $isCommandHandler
     * @param bool        $validationEnabled
     * @param array       $validationGroups
     * @param string|null $defaultValidationFailedEvent
     * @param string|null $defaultThrowableEvent
     */
    private function __construct(
        bool $isEventListener,
        bool $isCommandHandler,
        bool $validationEnabled = false,
        array $validationGroups = [],
        ?string $defaultValidationFailedEvent = null,
        ?string $defaultThrowableEvent = null
    )
    {
        $this->isEventListener              = $isEventListener;
        $this->isCommandHandler             = $isCommandHandler;
        $this->validationEnabled            = $validationEnabled;
        $this->validationGroups             = $validationGroups;
        $this->defaultValidationFailedEvent = $defaultValidationFailedEvent;
        $this->defaultThrowableEvent        = $defaultThrowableEvent;
    }
}
