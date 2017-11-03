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

namespace Desperado\Framework\Listeners;

use Desperado\Framework\Events\AbstractFrameworkEvent;
use Desperado\Framework\FrameworkEventsInterface;
use Desperado\Framework\StorageManager\FlushProcessor;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
use Desperado\Framework\Events as FrameworkEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Execute flush storage
 */
final class FlushStoragesListener
{
    /**
     * Storage managers registry
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * Framework event dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param StorageManagerRegistry   $storageManagersRegistry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        StorageManagerRegistry $storageManagersRegistry,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->storageManagersRegistry = $storageManagersRegistry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Execute event
     *
     * @param AbstractFrameworkEvent $event
     *
     * @return void
     */
    public function __invoke(AbstractFrameworkEvent $event): void
    {
        $message = $event->getMessage();
        $context = $event->getExecutionContext();

        $this->eventDispatcher->dispatch(
            FrameworkEventsInterface::BEFORE_FLUSH_EXECUTION,
            new FrameworkEvents\OnFlushExecutionStartedEvent($message, $context)
        );

        $promise = (new FlushProcessor($this->storageManagersRegistry))->process($context);

        $promise->then(
            function() use ($message, $context)
            {
                $this->eventDispatcher->dispatch(
                    FrameworkEventsInterface::AFTER_FLUSH_EXECUTION,
                    new FrameworkEvents\OnFlushExecutionFinishedEvent($message, $context)
                );
            },
            function(\Throwable $throwable) use ($message, $context)
            {
                $this->eventDispatcher->dispatch(
                    FrameworkEventsInterface::FLUSH_EXECUTION_FAILED,
                    new FrameworkEvents\OnFlushExecutionFailedEvent($message, $context, $throwable)
                );
            }
        );
    }
}
