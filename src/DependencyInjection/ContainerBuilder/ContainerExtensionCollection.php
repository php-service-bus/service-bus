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

use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Extension collection
 */
final class ContainerExtensionCollection implements \IteratorAggregate
{
    /**
     * @var array<string, \Symfony\Component\DependencyInjection\Extension\Extension>
     */
    private $collection = [];

    /**
     * @noinspection PhpDocSignatureInspection
     *
     * @param Extension ...$extensions
     *
     * @return void
     */
    public function push(Extension ... $extensions): void
    {
        foreach($extensions as $extension)
        {
            $this->add($extension);
        }
    }

    /**
     * @param Extension $extension
     *
     * @return void
     */
    public function add(Extension $extension): void
    {
        $this->collection[\spl_object_hash($extension)] = $extension;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->collection;
    }
}
