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
 *
 * @psalm-immutable
 */
final class CommandHandler implements ServicesAnnotationsMarker
{
    /**
     * Command validation enabled.
     *
     * @var bool
     */
    public $validate = false;

    /**
     * In case of validation errors, automatically send the event and stop further execution
     * The event must implement @see ValidationFailedEvent interface.
     *
     * If no class is specified, control is passed to user code
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
     * @var string|null
     */
    public $defaultThrowableEvent = null;

    /**
     * Validation groups.
     *
     * @psalm-var array<int, string>
     *
     * @var array
     */
    public $groups = [];

    /**
     * Message description.
     * Will be added to the log when the method is called.
     *
     * @var string|null
     */
    public $description = null;

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
            if (\property_exists($this, $property) === false)
            {
                throw new \InvalidArgumentException(
                    \sprintf('Unknown property "%s" on annotation "%s"', $property, \get_class($this))
                );
            }

            $this->{$property} = $value;
        }
    }
}
