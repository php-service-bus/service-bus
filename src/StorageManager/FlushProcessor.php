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

use Desperado\Domain\CQRS\ContextInterface;
use Desperado\EventSourcing\Manager\AggregateStorageManagerInterface;
use Desperado\Framework\Application\ApplicationLogger;
use Desperado\Saga\SagaStorageManagerInterface;
use Psr\Log\LogLevel;
use React\Promise\Promise;
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
        return new Promise(
            function($resolve, $reject) use ($context)
            {
                try
                {
                    $this->commitManagers($this->registry->getAggregateManagers(), $context);
                    $this->commitManagers($this->registry->getSagaManagers(), $context);

                    $resolve();
                }
                catch(\Throwable $throwable)
                {
                    $reject($throwable);
                }
            }
        );
    }

    /**
     * Commit data
     *
     * @param array            $collection
     * @param ContextInterface $context
     *
     * @return PromiseInterface
     */
    private function commitManagers(array $collection, ContextInterface $context): PromiseInterface
    {
        $promises = \array_map(
            function($storageManager) use ($context)
            {
                /** @var SagaStorageManagerInterface|AggregateStorageManagerInterface $storageManager */
                return $storageManager
                    ->commit($context)
                    ->then(
                        function()
                        {
                            return true;
                        },
                        function(\Throwable $throwable)
                        {
                            ApplicationLogger::throwable('flushProcessor', $throwable, LogLevel::EMERGENCY);

                            return false;
                        }
                    );
            },
            $collection
        );

        return \React\Promise\all($promises);
    }
}
