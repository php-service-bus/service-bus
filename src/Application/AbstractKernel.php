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
use Desperado\Domain\CQRS\ContextInterface;
use Desperado\Domain\CQRS\MessageBusInterface;
use Desperado\Domain\EntryPoint\MessageRouterInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\EventSourcing\Service\EventSourcingService;
use Desperado\Framework\MessageProcessor;
use Desperado\Framework\Metrics\MetricsCollectorInterface;
use Desperado\Saga\Service\SagaService;

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
     * Aggregates service
     *
     * @var EventSourcingService
     */
    private $eventSourcingService;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * @param string                    $entryPointName
     * @param Environment               $environment
     * @param MessageRouterInterface    $messageRouter
     * @param MessageBusInterface       $messageBus
     * @param MetricsCollectorInterface $metricsCollector
     * @param EventSourcingService      $eventSourcingService
     * @param SagaService               $sagaService
     */
    public function __construct(
        string $entryPointName,
        Environment $environment,
        MessageRouterInterface $messageRouter,
        MessageBusInterface $messageBus,
        MetricsCollectorInterface $metricsCollector,
        EventSourcingService $eventSourcingService,
        SagaService $sagaService
    )
    {
        $this->entryPointName = $entryPointName;
        $this->environment = $environment;
        $this->messageRouter = $messageRouter;
        $this->messageBus = $messageBus;
        $this->metricsCollector = $metricsCollector;
        $this->eventSourcingService = $eventSourcingService;
        $this->sagaService = $sagaService;

        $this->messageProcessor = new MessageProcessor(
            $this->messageBus,
            $this->eventSourcingService,
            $this->sagaService
        );
    }

    /**
     * Handle message
     *
     * @param AbstractMessage  $message
     * @param ContextInterface $context
     *
     * @return void
     */
    final public function handle(AbstractMessage $message, ContextInterface $context): void
    {
        $context = $this->createApplicationContext($context);

        $this->messageProcessor->execute($message, $context);
    }

    /**
     * Create application context
     *
     * @param ContextInterface $originContext
     *
     * @return AbstractApplicationContext
     */
    abstract protected function createApplicationContext(
        ContextInterface $originContext
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
     * Get event sourcing service
     *
     * @return EventSourcingService
     */
    final protected function getEventSourcingService(): EventSourcingService
    {
        return $this->eventSourcingService;
    }

    /**
     * Get sagas service
     *
     * @return SagaService
     */
    final protected function getSagaService(): SagaService
    {
        return $this->sagaService;
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
}
