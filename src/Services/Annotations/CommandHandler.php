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
    private $validate = false;

    /**
     * Validation groups
     *
     * @var array<int, string>
     */
    private $groups = [];

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

    /**
     * Command validation enabled?
     *
     * @return bool
     */
    public function validationEnabled(): bool
    {
        return $this->validate;
    }

    /**
     * Receive validation groups
     *
     * @return array<int, string>
     */
    public function validationGroups(): array
    {
        return $this->groups;
    }
}
