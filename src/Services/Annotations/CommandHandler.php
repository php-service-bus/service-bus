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
 * Annotation indicating to the command handler.
 *
 * The command should implement the interface "\ServiceBus\Common\Messages\Command"
 *
 * @Annotation
 * @Target("METHOD")
 */
final class CommandHandler implements ServicesAnnotationsMarker
{
    /**
     * Command validation enabled.
     */
    public bool $validate = false;

    /**
     * In case of validation errors, automatically send the event and stop further execution
     * The event must implement @see ValidationFailedEvent interface.
     *
     * If no class is specified, control is passed to user code
     */
    public ?string $defaultValidationFailedEvent = null;

    /**
     * In case of a runtime error, automatically send the specified event with the message received from the exception
     * The event must implement @see ExecutionFailedEvent interface.
     *
     * If no class is specified, control is passed to user code
     */
    public ?string $defaultThrowableEvent= null;

    /**
     * Validation groups.
     *
     * @psalm-var array<int, string>
     */
    public array $groups = [];

    /**
     * @psalm-param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        /** @var array|string|null $value */
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
