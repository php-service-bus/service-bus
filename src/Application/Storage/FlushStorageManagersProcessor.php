<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application\Storage;

use Desperado\Framework\Application\Context\KernelContext;
use Desperado\Framework\Common\Formatter\ThrowableFormatter;
use Desperado\Framework\Infrastructure\StorageManager\StorageManagerInterface;
use Psr\Log\LoggerInterface;
use React\Promise\Deferred;

/**
 * Preservation of observed entities; publishing events / sending commands
 */
class FlushStorageManagersProcessor
{
    /**
     * Storage manager registry
     *
     * @var StorageManagerRegistry
     */
    private $registry;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param StorageManagerRegistry $registry
     * @param LoggerInterface        $logger
     */
    public function __construct(StorageManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Execute preservation of observed entities; publishing events / sending commands
     *
     * @param KernelContext $context
     *
     * @return void
     */
    public function process(KernelContext $context): void
    {
        $failHandler = function(\Throwable $throwable)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));
        };

        $deferred = new Deferred();
        $deferred
            ->promise()
            ->then(
                function(StorageManagerInterface $storageManager) use ($context)
                {
                    $storageManager->commit($context);
                },
                $failHandler
            )
            ->then(null, $failHandler);

        foreach($this->registry as $storageManager)
        {
            /** @var StorageManagerInterface $storageManager */

            $deferred->resolve($storageManager);
        }
    }
}
