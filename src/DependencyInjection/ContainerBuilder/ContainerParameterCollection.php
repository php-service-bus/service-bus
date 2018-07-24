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

namespace Desperado\ServiceBus\DependencyInjection\ContainerBuilder;

/**
 * Container parameters collection
 */
final class ContainerParameterCollection implements \IteratorAggregate
{
    /**
     * Key=>value parameters
     *
     * @var array<string, mixed>
     */
    private $collection = [];

    /**
     * @param array<string, mixed> $parameters
     *
     * @return void
     */
    public function push(array $parameters): void
    {
        foreach($parameters as $key => $value)
        {
            $this->add($key, $value);
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function add(string $key, $value): void
    {
        $this->collection[$key] = $value;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->collection;
    }
}
