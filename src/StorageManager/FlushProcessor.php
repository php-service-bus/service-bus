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
     * @return void
     */
    public function process(ContextInterface $context): void
    {
        $this->commitManagers($this->registry->getAggregateManagers(), $context);
        $this->commitManagers($this->registry->getSagaManagers(), $context);
    }

    /**
     * Commit data
     *
     * @param array            $collection
     * @param ContextInterface $context
     *
     * @return void
     */
    private function commitManagers(array $collection, ContextInterface $context): void
    {
        foreach($collection as $storageManager)
        {
            /** @var SagaStorageManagerInterface|AggregateStorageManagerInterface $storageManager */

            $storageManager->commit($context);
        }
    }
}
