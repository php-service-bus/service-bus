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

namespace Desperado\ServiceBus\MessageHandlers;

use Desperado\ServiceBus\Services\Contracts\ExecutionFailedEvent;
use Desperado\ServiceBus\Services\Contracts\ValidationFailedEvent;

/**
 * Execution options
 */
final class HandlerOptions
{
    /**
     * Validation enabled
     *
     * @var bool
     */
    private $validationEnabled = false;

    /**
     * Validation groups
     *
     * @var array<int, string>
     */
    private $validationGroups = [];

    /**
     * In case of validation errors, automatically send the event and stop further execution
     * The event must implement @see ValidationFailedEvent interface
     *
     * If no class is specified, control is passed to user code
     *
     * @var string|null
     */
    private $defaultValidationFailedEvent;

    /**
     * In case of a runtime error, automatically send the specified event with the message received from the exception
     * The event must implement @see ExecutionFailedEvent interface
     *
     * If no class is specified, control is passed to user code
     *
     * @var string|null
     */
    private $defaultThrowableEvent;

    /**
     * @param string $eventClass
     *
     * @return void
     *
     * @throws \LogicException
     *
     * @throws \LogicException Event class must implement @see ExecutionFailedEvent
     */
    public function useDefaultValidationFailedEvent(string $eventClass): void
    {
        if(false === \is_a($eventClass, ValidationFailedEvent::class, true))
        {
            throw new \LogicException(
                \sprintf('Event class "%s" must implement "%s" interface', $eventClass, ValidationFailedEvent::class)
            );
        }

        $this->defaultValidationFailedEvent = $eventClass;
    }

    /**
     * @param string $eventClass
     *
     * @return void
     *
     * @throws \LogicException Event class must implement @see ExecutionFailedEvent
     */
    public function useDefaultThrowableEvent(string $eventClass): void
    {
        if(false === \is_a($eventClass, ExecutionFailedEvent::class, true))
        {
            throw new \LogicException(
                \sprintf('Event class "%s" must implement "%s" interface', $eventClass, ExecutionFailedEvent::class)
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
     * Is validation enabled
     *
     * @return bool
     */
    public function validationEnabled(): bool
    {
        return $this->validationEnabled;
    }

    /**
     * Receive validation groups
     *
     * @return array<int, string>
     */
    public function validationGroups(): array
    {
        return $this->validationGroups;
    }

    /**
     * Is the default validation failed event specified
     *
     * @return bool
     */
    public function hasDefaultValidationFailedEvent(): bool
    {
        return null !== $this->defaultValidationFailedEvent;
    }

    /**
     * Receive validation error class
     *
     * @return string|null
     */
    public function defaultValidationFailedEvent(): ?string
    {
        return $this->defaultValidationFailedEvent;
    }

    /**
     * Is the default error event specified
     *
     * @return bool
     */
    public function hasDefaultThrowableEvent(): bool
    {
        return null !== $this->defaultThrowableEvent;
    }

    /**
     * Receive error event class
     *
     * @return string|null
     */
    public function defaultThrowableEvent(): ?string
    {
        return $this->defaultThrowableEvent;
    }
}
