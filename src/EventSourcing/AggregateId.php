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
use Desperado\ServiceBus\EventSourcing\Exceptions\EmptyAggregateIdentifierNotAllowed;

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
     * @return static
     */
    public static function new(): self
    {
        return new static(uuid());
    }

    /**
     * @param string $id
     *
     * @throws \Desperado\ServiceBus\EventSourcing\Exceptions\EmptyAggregateIdentifierNotAllowed
     */
    final public function __construct(string $id)
    {
        if('' === $id)
        {
            throw new EmptyAggregateIdentifierNotAllowed();
        }

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
