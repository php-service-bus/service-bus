<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\StorageManager;

use Desperado\Domain\ContextInterface;
use Desperado\EventSourcing\AggregateStorageManagerInterface;
use Desperado\EventSourcing\Saga\SagaStorageManagerInterface;
use React\Promise\PromiseInterface;

/**
 * Flush storage managers processor
 */
class FlushProcessor
{
    /**
     * Storage managers registry
     *
     * @var StorageManagerRegistry
     */
    private $registry;

    /**
     * @param StorageManagerRegistry $registry
     */
    public function __construct(StorageManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ContextInterface $context
     *
     * @return PromiseInterface
     */
    public function process(ContextInterface $context): PromiseInterface
    {
        $promises = [];

        \array_map(
            function(AggregateStorageManagerInterface $aggregateStorageManager) use ($context, &$promises)
            {
                $promises[] = $aggregateStorageManager->commit($context);
            },
            $this->registry->getAggregateManagers()
        );

        \array_map(
            function(SagaStorageManagerInterface $sagaStorageManager) use ($context, &$promises)
            {
                $promises[] = $sagaStorageManager->commit($context);
            },
            $this->registry->getSagaManagers()
        );

        return \React\Promise\all($promises);
    }

}
