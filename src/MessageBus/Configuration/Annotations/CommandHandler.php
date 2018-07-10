<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Configuration\Annotations;

/**
 * Annotation indicating to the command handler
 *
 * The command should implement the interface "\Desperado\Contracts\Common\Command"
 *
 * @Annotation
 * @Target("METHOD")
 */
final class CommandHandler implements ServiceBusAnnotationMarker
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
     * @var array
     */
    private $groups = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach($data as $property => $value)
        {
            if(true === \property_exists($this, $property))
            {
                $this->{$property} = $value;
            }
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
     * @return array
     */
    public function validationGroups(): array
    {
        return $this->groups;
    }
}
