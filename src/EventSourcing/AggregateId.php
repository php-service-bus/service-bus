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

namespace Desperado\ServiceBus\EventSourcing;

use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\EventSourcing\Exceptions\InvalidAggregateIdentifier;

/**
 * Base aggregate identifier class
 */
abstract class AggregateId
{
    /**
     * Identifier
     *
     * @var string
     */
    private $id;

    /**
     * Aggregate class
     *
     * @psalm-var class-string<\Desperado\ServiceBus\EventSourcing\Aggregate>
     *
     * @var string
     */
    private $aggregateClass;

    /**
     * @param string $aggregateClass
     *
     * @return static
     */
    public static function new(string $aggregateClass): self
    {
        return new static(uuid(), $aggregateClass);
    }

    /**
     * @param string $id
     * @param string $aggregateClass
     *
     * @throws \Desperado\ServiceBus\EventSourcing\Exceptions\InvalidAggregateIdentifier
     */
    final public function __construct(string $id, string $aggregateClass)
    {
        if('' === $id)
        {
            throw new InvalidAggregateIdentifier('The aggregate identifier can\'t be empty');
        }

        if('' === $aggregateClass || false === \class_exists($aggregateClass))
        {
            throw new InvalidAggregateIdentifier(
                \sprintf('Invalid saga class specified ("%s")', $aggregateClass)
            );
        }

        /** @psalm-var class-string<\Desperado\ServiceBus\EventSourcing\Aggregate> $aggregateClass */

        $this->id             = $id;
        $this->aggregateClass = $aggregateClass;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Get aggregate class
     *
     * @psalm-return class-string<\Desperado\ServiceBus\EventSourcing\Aggregate>
     *
     * @return string
     */
    public function aggregateClass(): string
    {
        return $this->aggregateClass;
    }
}
