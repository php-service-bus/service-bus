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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * CompilerPass collection
 */
final class ContainerCompilerPassCollection implements \IteratorAggregate
{
    /**
     * @var array<string , \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface>
     */
    private $collection = [];

    /**
     * @noinspection PhpDocSignatureInspection
     *
     * @param CompilerPassInterface ...$compilerPasses
     *
     * @return void
     */
    public function push(CompilerPassInterface ...$compilerPasses): void
    {
        foreach($compilerPasses as $compilerPass)
        {
            $this->add($compilerPass);
        }
    }

    /**
     * @param CompilerPassInterface $compilerPass
     *
     * @return void
     */
    public function add(CompilerPassInterface $compilerPass): void
    {
        $this->collection[\spl_object_hash($compilerPass)] = $compilerPass;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->collection;
    }
}
