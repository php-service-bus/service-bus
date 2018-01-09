<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use Desperado\Saga\Service\Exceptions as SagaServiceExceptions;
use Desperado\Saga\Service\SagaService;
use Desperado\ServiceBus\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\Extensions\Logger\ServiceBusLogger;
use Desperado\ServiceBus\KernelEvents;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Services;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Base application kernel
 */
abstract class AbstractKernel
{
    /**
     * Message bus factory
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * Message bus
     *
     * @var MessageBus
     */
    private $messageBus;

    /**
     * Event dispatcher
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param MessageBusBuilder $messageBusBuilder
     * @param SagaService       $sagaService
     * @param EventDispatcher   $dispatcher
     *
     * @throws Services\Exceptions\ServiceConfigurationExceptionInterface
     * @throws SagaServiceExceptions\ClosedMessageBusException
     * @throws SagaServiceExceptions\SagaClassWasNotFoundException
     */
    final public function __construct(
        MessageBusBuilder $messageBusBuilder,
        SagaService $sagaService,
        EventDispatcher $dispatcher
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->eventDispatcher = $dispatcher;
        $this->sagaService = $sagaService;

        $this->configureSagas();
        $this->configureServices();

        $this->messageBus = $messageBusBuilder->build();
    }

    /**
     * Handle message
     *
     * @param EntryPointContext        $entryPointContext
     * @param AbstractExecutionContext $executionContext
     *
     * @return PromiseInterface
     *
     * @throws \Throwable
     */
    final public function handle(EntryPointContext $entryPointContext, AbstractExecutionContext $executionContext): PromiseInterface
    {
        $this->eventDispatcher->dispatch(
            KernelEvents\MessageIsReadyForProcessingEvent::EVENT_NAME,
            new KernelEvents\MessageIsReadyForProcessingEvent($entryPointContext, $executionContext)
        );

        $promise = $this->messageBus->handle(
            $entryPointContext->getMessage(),
            $executionContext
        );

        return $promise
            ->then(
                function() use ($entryPointContext, $executionContext)
                {
                    $this->eventDispatcher->dispatch(
                        KernelEvents\MessageProcessingCompletedEvent::EVENT_NAME,
                        new KernelEvents\MessageProcessingCompletedEvent($entryPointContext, $executionContext)
                    );

                    return $executionContext->getOutboundMessageContext();
                },
                function(\Throwable $throwable) use ($entryPointContext, $executionContext)
                {
                    ServiceBusLogger::throwable('kernel', $throwable);

                    $this->eventDispatcher->dispatch(
                        KernelEvents\MessageProcessingFailedEvent::EVENT_NAME,
                        new KernelEvents\MessageProcessingFailedEvent($throwable, $entryPointContext, $executionContext)
                    );

                    return $throwable;
                }
            );
    }

    /**
     * Get sagas list
     *
     * [
     *     0 => 'someSagaNamespace',
     *     1 => 'someSagaNamespace',
     *     ....
     * ]
     *
     *
     * @return array
     */
    protected function getSagasList(): array
    {
        return [];
    }

    /**
     * Get application services
     *
     * @return Services\ServiceInterface[]
     */
    protected function getServices(): array
    {
        return [];
    }

    /**
     * Get message bus builder
     *
     * @return MessageBusBuilder
     */
    final protected function getMessageBusBuilder(): MessageBusBuilder
    {
        return $this->messageBusBuilder;
    }

    /**
     * Process saga configuration
     *
     * @return void
     *
     * @throws SagaServiceExceptions\ClosedMessageBusException
     * @throws SagaServiceExceptions\SagaClassWasNotFoundException
     */
    private function configureSagas(): void
    {
        foreach($this->getSagasList() as $saga)
        {
            $this->sagaService->configure($saga);

            /** Add saga listeners to message bus */

            foreach($this->sagaService->getSagaListeners($saga) as $listener)
            {
                $this->messageBusBuilder->pushMessageHandler(
                    Services\Handlers\Messages\MessageHandlerData::new(
                        $listener->getEventNamespace(),
                        $listener->getHandler(),
                        new Services\Handlers\Messages\EventExecutionParameters('sagas')
                    )
                );
            }
        }
    }

    /**
     * Process services configuration
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    private function configureServices(): void
    {
        foreach($this->getServices() as $service)
        {
            $this->messageBusBuilder->applyService($service);
        }
    }
}
