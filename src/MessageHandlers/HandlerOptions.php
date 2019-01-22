<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\MessageHandlers;

use ServiceBus\Services\Contracts\ExecutionFailedEvent;
use ServiceBus\Services\Contracts\ValidationFailedEvent;
use ServiceBus\Services\Exceptions\InvalidEventType;

/**
 * Execution options
 *
 * @property-read bool               $validationEnabled
 * @property-read array<int, string> $validationGroups
 * @property-read string|null        $defaultValidationFailedEvent
 * @property-read string|null        $defaultThrowableEvent
 */
final class HandlerOptions
{
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
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param string $eventClass
     *
     * @return void
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType Event class must implement @see ExecutionFailedEvent
     */
    public function useDefaultValidationFailedEvent(string $eventClass): void
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

        $this->defaultValidationFailedEvent = $eventClass;
    }

    /**
     * @param string $eventClass
     *
     * @return void
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType Event class must implement @see ExecutionFailedEvent
     */
    public function useDefaultThrowableEvent(string $eventClass): void
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

        $this->defaultThrowableEvent = $eventClass;
    }

    /**
     * @param array<int, string> $validationGroups
     *
     * @return void
     */
    public function enableValidation(array $validationGroups = []): void
    {
        $this->validationEnabled = true;
        $this->validationGroups  = $validationGroups;
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
