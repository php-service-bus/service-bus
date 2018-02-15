<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Kernel;

use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\Domain\ThrowableFormatter;
use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\ServiceBus\Saga\Exceptions as SagaProviderExceptions;
use Desperado\ServiceBus\Application\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\Application\Kernel\Events as KernelEvents;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Services;
use Desperado\ServiceBus\Task\CompletedTask;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;
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
     * @var SagaProvider
     */
    private $sagaProvider;

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
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessageBusBuilder $messageBusBuilder
     * @param SagaProvider      $sagaProvider
     * @param EventDispatcher   $dispatcher
     * @param LoggerInterface   $logger
     *
     * @throws Services\Exceptions\ServiceConfigurationExceptionInterface
     * @throws SagaProviderExceptions\ClosedMessageBusException
     */
    final public function __construct(
        MessageBusBuilder $messageBusBuilder,
        SagaProvider $sagaProvider,
        EventDispatcher $dispatcher,
        LoggerInterface $logger
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->eventDispatcher = $dispatcher;
        $this->sagaProvider = $sagaProvider;
        $this->logger = $logger;

        $this->configureSagas();
        $this->init();

        $this->messageBus = $messageBusBuilder->build();
    }

    /**
     * Handle message
     *
     * @param EntryPointContext         $entryPointContext
     * @param ExecutionContextInterface $executionContext
     *
     * @return PromiseInterface
     *
     * @throws \Throwable
     */
    final public function handle(
        EntryPointContext $entryPointContext,
        ExecutionContextInterface $executionContext
    ): PromiseInterface
    {
        $this->eventDispatcher->dispatch(
            KernelEvents\MessageIsReadyForProcessingEvent::EVENT_NAME,
            new KernelEvents\MessageIsReadyForProcessingEvent($entryPointContext, $executionContext)
        );

        $promise = $this->messageBus->handle(
            $entryPointContext->getMessage(),
            $executionContext
        );

        return $promise->then(
            function(array $results) use ($executionContext, $entryPointContext)
            {
                /** No handlers found */
                if(0 === \count($results))
                {
                    return null;
                }

                $rejectedTasks = $this->collectRejectedTasks($results);

                if(0 !== \count($rejectedTasks))
                {
                    $this->processRejectedTasks($rejectedTasks, $entryPointContext, $executionContext);

                    return null;
                }

                return $this->processSuccessTasks($results, $entryPointContext, $executionContext);
            },
            function(\Throwable $throwable) use ($entryPointContext, $executionContext)
            {
                $this->logger->critical(ThrowableFormatter::toString($throwable));

                return $throwable;
            }
        );
    }

    /**
     * Custom initialization (before message bust compilation)
     *
     * @return void
     */
    protected function init(): void
    {

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
     * @throws SagaProviderExceptions\ClosedMessageBusException
     */
    private function configureSagas(): void
    {
        foreach($this->getSagasList() as $saga)
        {
            $this->sagaProvider->configure($saga);

            /** Add saga listeners to message bus */

            foreach($this->sagaProvider->getSagaListeners($saga) as $listener)
            {
                $this->messageBusBuilder->pushMessageHandler(
                    Services\Handlers\MessageHandlerData::new(
                        $listener->getEventNamespace(),
                        $listener->getHandler(),
                        [],
                        new Services\Handlers\EventExecutionParameters('sagas')
                    )
                );
            }
        }
    }

    /**
     * Process success tasks
     *
     * @param array                     $completedTasks
     * @param EntryPointContext         $entryPointContext
     * @param ExecutionContextInterface $executionContext
     *
     * @return OutboundMessageContextInterface[]
     */
    private function processSuccessTasks(
        array $completedTasks,
        EntryPointContext $entryPointContext,
        ExecutionContextInterface $executionContext

    ): array
    {
        $resultContexts = \array_filter(
            \array_map(
                function(CompletedTask $completedTask)
                {
                    return $completedTask->getContext()->getOutboundMessageContext();
                },
                $completedTasks
            )
        );

        $this->eventDispatcher->dispatch(
            KernelEvents\MessageProcessingCompletedEvent::EVENT_NAME,
            new KernelEvents\MessageProcessingCompletedEvent($entryPointContext, $executionContext)
        );

        return $resultContexts;
    }

    /**
     * Collecting tasks that resulted in an error
     *
     * @param array $completedTasks
     *
     * @return CompletedTask[]
     */
    private function collectRejectedTasks(array $completedTasks): array
    {
        return \array_filter(
            \array_map(
                function(CompletedTask $completedTask)
                {
                    return $completedTask->getTaskResult() instanceof RejectedPromise
                        ? $completedTask
                        : null;
                },
                $completedTasks
            )
        );
    }

    /**
     * Processing completed with error messages
     *
     * @param CompletedTask[]           $rejectedTasks
     * @param EntryPointContext         $entryPointContext
     * @param ExecutionContextInterface $executionContext
     *
     * @return void
     */
    private function processRejectedTasks(
        array $rejectedTasks,
        EntryPointContext $entryPointContext,
        ExecutionContextInterface $executionContext
    ): void
    {
        foreach($rejectedTasks as $rejectedTask)
        {
            $rejectedTask->getTaskResult()
                ->then(
                    null,
                    function(\Throwable $throwable) use ($entryPointContext, $executionContext)
                    {
                        $this->eventDispatcher->dispatch(
                            KernelEvents\MessageProcessingFailedEvent::EVENT_NAME,
                            new KernelEvents\MessageProcessingFailedEvent(
                                $throwable,
                                $entryPointContext,
                                $executionContext
                            )
                        );

                        $this->logger->error(ThrowableFormatter::toString($throwable));
                    }
                );
        }
    }
}
