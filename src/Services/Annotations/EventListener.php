<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Annotations;

/**
 * Annotation indicating to the event listener.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class EventListener implements ServicesAnnotationsMarker
{
    /**
     * Event validation enabled.
     *
     * @var bool
     */
    public $validate = false;

    /**
     * Validation groups.
     *
     * @psalm-var array<int, string>
     *
     * @var array
     */
    public $groups = [];

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

    /**
     * @psalm-param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        /** @psalm-var array|string|class-string|null $value */
        foreach ($data as $property => $value)
        {
            if (false === \property_exists($this, $property))
            {
                throw new \InvalidArgumentException(
                    \sprintf('Unknown property "%s" on annotation "%s"', $property, \get_class($this))
                );
            }

            $this->{$property} = $value;
        }
    }
}
