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

namespace Desperado\ServiceBus\EventSourcingProjections;

/**
 *
 */
final class ProjectionsListener
{
    /**
     * Aggregate class
     *
     * @psalm-var class-string<\Desperado\ServiceBus\EventSourcing\Aggregate>|null
     *
     * @var string|null
     */
    public $aggregateClass;

    /**
     * Read model class
     *
     * @psalm-var class-string<\Desperado\ServiceBus\EventSourcingProjections\ReadModel>|null
     *
     * @var string|null
     */
    public $readModelClass;

    /**
     * Projector type
     *
     * @var string|null
     */
    public $projector;

    /**
     * Projector options
     * For example, if the type of the projector is "postgres", it is mandatory to specify the name of the table
     *
     * @var array<string, string|int|float|null>
     */
    public $projectorOptions = [];

    /**
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        /** @var string|null $value */
        foreach($data as $key => $value)
        {
            if(false === \property_exists($this, $key))
            {
                throw new \InvalidArgumentException(
                    \sprintf('Unknown property "%s" on annotation "%s"', $key, \get_class($this))
                );
            }

            $this->{$key} = $value;
        }
    }
}
