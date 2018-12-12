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

namespace Desperado\ServiceBus\Services\Annotations;

/**
 * Annotation indicating to the command handler
 *
 * The command should implement the interface "\Desperado\Contracts\Common\Command"
 *
 * @Annotation
 * @Target("METHOD")
 */
final class CommandHandler implements ServicesAnnotationsMarker
{
    /**
     * Command validation enabled
     *
     * @var bool
     */
    public $validate = false;

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
     * Validation groups
     *
     * @var array<int, string>
     */
    public $groups = [];

    /**
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        /**
         * @var string     $property
         * @var array|bool $value
         */
        foreach($data as $property => $value)
        {
            if(false === \property_exists($this, $property))
            {
                throw new \InvalidArgumentException(
                    \sprintf('Unknown property "%s" on annotation "%s"', $property, \get_class($this))
                );
            }

            $this->{$property} = $value;
        }
    }
}
