<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus;

use Desperado\ServiceBus\KernelEvents\MessageBusCompiledEvent;
use Desperado\ServiceBus\Task\Behaviors;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\MessageBus\Exceptions\MessageBusAlreadyCreatedException;
use Desperado\ServiceBus\Services\ServiceHandlersExtractorInterface;
use Desperado\ServiceBus\Services\ServiceInterface;
use Desperado\ServiceBus\Task\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Build message bus
 */
class MessageBusBuilder
{
    /**
     * Message handlers
     *
     * @var Handlers\MessageHandlersCollection
     */
    private $messageHandlers;

    /**
     * Behaviors collection
     *
     * @var Behaviors\BehaviorInterface[]
     */
    private $behaviors = [];

    /**
     * Handlers extractor
     *
     * @var ServiceHandlersExtractorInterface
     */
    private $serviceHandlersExtractor;

    /**
     * Event dispatcher
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Is message bus created
     *
     * @var bool
     */
    private $isCompiled = false;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ServiceHandlersExtractorInterface $serviceHandlersExtractor
     * @param EventDispatcher                   $eventDispatcher
     * @param LoggerInterface                   $logger
     */
    public function __construct(
        ServiceHandlersExtractorInterface $serviceHandlersExtractor,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger = null
    )
    {
        $this->serviceHandlersExtractor = $serviceHandlersExtractor;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;

        $this->messageHandlers = Handlers\MessageHandlersCollection::create();
    }

    /**
     * Push behavior instance
     *
     * @param Behaviors\BehaviorInterface $behavior
     *
     * @return void
     *
     * @throws MessageBusAlreadyCreatedException
     */
    public function pushBehavior(Behaviors\BehaviorInterface $behavior): void
    {
        $this->guardIsCompiled();

        $this->behaviors[\get_class($behavior)] = $behavior;
    }

    /**
     * Push message handler
     *
     * @param Handlers\MessageHandlerData $messageHandlerData
     *
     * @return void
     *
     * @throws MessageBusAlreadyCreatedException
     */
    public function pushMessageHandler(Handlers\MessageHandlerData $messageHandlerData): void
    {
        $this->guardIsCompiled();

        $this->messageHandlers->add($messageHandlerData);
    }

    /**
     * Apply service
     * Parse service handlers (command handler, event listener, error handler)
     *
     * @param ServiceInterface $service
     *
     * @return void
     *
     * @throws MessageBusAlreadyCreatedException
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function applyService(ServiceInterface $service): void
    {
        $this->guardIsCompiled();

        $defaultServiceLoggerChannel = $this->serviceHandlersExtractor->extractServiceLoggerChannel($service);
        $handlers = $this->serviceHandlersExtractor->extractHandlers($service, $defaultServiceLoggerChannel);

        foreach($handlers as $handlerData)
        {
            $this->pushMessageHandler($handlerData);
        }
    }

    /**
     * Get compiled message bus flag
     *
     * @return bool
     */
    public function isCompiled(): bool
    {
        return $this->isCompiled;
    }

    /**
     * Build messages bus
     *
     * @return MessageBus
     *
     * @throws MessageBusAlreadyCreatedException
     */
    public function build(): MessageBus
    {
        $this->guardIsCompiled();

        $taskCollection = $this->prepareTaskCollection();

        $messageBus = MessageBus::build(
            $taskCollection,
            $this->logger
        );

        $this->isCompiled = true;

        $this->eventDispatcher->dispatch(
            MessageBusCompiledEvent::EVENT_NAME,
            new MessageBusCompiledEvent($taskCollection->count())
        );

        return $messageBus;
    }

    /**
     * Create a collection of tasks
     *
     * @return MessageBusTaskCollection
     */
    private function prepareTaskCollection(): MessageBusTaskCollection
    {
        $collection = MessageBusTaskCollection::createEmpty();

        foreach($this->messageHandlers as $handlerData)
        {
            /** @var Handlers\MessageHandlerData $handlerData */

            $task = Task::new(
                $handlerData->getMessageHandler(),
                $handlerData->getExecutionOptions()
            );

            foreach($this->behaviors as $behavior)
            {
                /** The task is an immutable object */
                $task = $behavior->apply($task);
            }

            $collection->add(
                MessageBusTask::create(
                    $handlerData->getMessageClassNamespace(),
                    $task,
                    $handlerData->getAutowiringServices()
                )
            );
        }

        return $collection;
    }

    /**
     * Make sure that the bus is not yet configured
     *
     * @return void
     *
     * @throws MessageBusAlreadyCreatedException
     */
    private function guardIsCompiled(): void
    {
        if(true === $this->isCompiled)
        {
            throw new MessageBusAlreadyCreatedException();
        }
    }
}
