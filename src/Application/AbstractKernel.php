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

namespace Desperado\Framework\Application;

use Desperado\CQRS\MessageBus;
use Desperado\Domain\ContextInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\MessageBusInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Framework\FrameworkEventsInterface;
use Desperado\Framework\Listeners as FrameworkListeners;
use Desperado\Framework\MessageProcessor;
use Desperado\Framework\Metrics\MetricsCollectorInterface;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Application kernel
 */
abstract class AbstractKernel
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Storage managers registry
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * Message router
     *
     * @var MessageRouterInterface
     */
    private $messageRouter;

    /**
     * Message bus
     *
     * @var MessageBus
     */
    private $messageBus;

    /**
     * Metrics collector
     *
     * @var MetricsCollectorInterface
     */
    private $metricsCollector;

    /**
     * Message execution processor
     *
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * Core event dispatcher
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param string                    $entryPointName
     * @param Environment               $environment
     * @param StorageManagerRegistry    $storageManagersRegistry
     * @param MessageRouterInterface    $messageRouter
     * @param MessageBusInterface       $messageBus
     * @param MetricsCollectorInterface $metricsCollector
     */
    public function __construct(
        string $entryPointName,
        Environment $environment,
        StorageManagerRegistry $storageManagersRegistry,
        MessageRouterInterface $messageRouter,
        MessageBusInterface $messageBus,
        MetricsCollectorInterface $metricsCollector
    )
    {
        $this->entryPointName = $entryPointName;
        $this->environment = $environment;
        $this->storageManagersRegistry = $storageManagersRegistry;
        $this->messageRouter = $messageRouter;
        $this->messageBus = $messageBus;
        $this->metricsCollector = $metricsCollector;

        $this->eventDispatcher = new EventDispatcher();

        $this->initDefaultCoreListeners();

        $this->messageProcessor = new MessageProcessor(
            $this->eventDispatcher,
            $this->messageBus
        );
    }

    /**
     * Handle message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return PromiseInterface
     */
    final public function handle(MessageInterface $message, ContextInterface $context): PromiseInterface
    {
        $context = $this->createApplicationContext($context, $this->storageManagersRegistry);


        return $this->messageProcessor->execute($message, $context);
    }

    /**
     * Create application context
     *
     * @param ContextInterface       $originContext
     * @param StorageManagerRegistry $storageManagersRegistry
     *
     * @return AbstractApplicationContext
     */
    abstract protected function createApplicationContext(
        ContextInterface $originContext,
        StorageManagerRegistry $storageManagersRegistry
    ): AbstractApplicationContext;

    /**
     * Get entry point name
     *
     * @return string
     */
    final protected function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * Get environment
     *
     * @return Environment
     */
    final protected function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Get message router
     *
     * @return MessageRouterInterface
     */
    final protected function getMessageRouter(): MessageRouterInterface
    {
        return $this->messageRouter;
    }

    /**
     * Get get metrics collector
     *
     * @return MetricsCollectorInterface
     */
    final protected function getMetricsCollector(): MetricsCollectorInterface
    {
        return $this->metricsCollector;
    }

    /**
     * Get storage manager registry
     *
     * @return StorageManagerRegistry
     */
    final protected function getStorageManagersRegistry(): StorageManagerRegistry
    {
        return $this->storageManagersRegistry;
    }

    /**
     * Add framework core event listener
     *
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     *
     * @return void
     */
    final protected function addCoreEventListener(string $eventName, callable $listener, int $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * Init default event listeners
     *
     * @return void
     */
    private function initDefaultCoreListeners(): void
    {
        $flushManagersListener = new FrameworkListeners\FlushStoragesListener(
            $this->storageManagersRegistry,
            $this->eventDispatcher
        );

        $collection = [
            FrameworkEventsInterface::BEFORE_MESSAGE_EXECUTION => [
                new FrameworkListeners\StartMessageExecutionListener()
            ],
            FrameworkEventsInterface::AFTER_MESSAGE_EXECUTION  => [
                $flushManagersListener
            ],
            FrameworkEventsInterface::MESSAGE_EXECUTION_FAILED => [
                $flushManagersListener
            ]
        ];

        foreach($collection as $key => $listeners)
        {
            foreach($listeners as $listener)
            {
                $this->addCoreEventListener($key, $listener);
            }
        }
    }
}
