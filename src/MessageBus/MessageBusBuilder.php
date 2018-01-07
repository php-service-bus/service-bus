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

use Desperado\ServiceBus\Task\Behaviors;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\MessageBus\Exceptions\MessageBusAlreadyCreatedException;
use Desperado\ServiceBus\Services\ServiceHandlersExtractorInterface;
use Desperado\ServiceBus\Services\ServiceInterface;
use Desperado\ServiceBus\Task\Task;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
class MessageBusBuilder
{
    /**
     * Message handlers
     *
     * @var Handlers\Messages\MessageHandlersCollection
     */
    private $messageHandlers;

    /**
     * Error handlers
     *
     * @var Handlers\Exceptions\ExceptionHandlersCollection
     */
    private $errorHandlers;

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
     * @param LoggerInterface|null              $logger
     */
    public function __construct(
        ServiceHandlersExtractorInterface $serviceHandlersExtractor,
        LoggerInterface $logger = null
    )
    {
        $this->serviceHandlersExtractor = $serviceHandlersExtractor;
        $this->logger = $logger ?? new NullLogger();

        $this->messageHandlers = Handlers\Messages\MessageHandlersCollection::create();
        $this->errorHandlers = Handlers\Exceptions\ExceptionHandlersCollection::create();
    }

    /**
     * Push behavior instance
     *
     * @param Behaviors\BehaviorInterface $behavior
     *
     * @return void
     */
    public function pushBehavior(Behaviors\BehaviorInterface $behavior): void
    {
        $this->behaviors[\get_class($behavior)] = $behavior;
    }

    /**
     * Push message handler
     *
     * @param Handlers\Messages\MessageHandlerData $messageHandlerData
     *
     * @return void
     */
    public function pushMessageHandler(Handlers\Messages\MessageHandlerData $messageHandlerData): void
    {
        $this->messageHandlers->add($messageHandlerData);
    }

    /**
     * Push service error handler
     *
     * @param Handlers\Exceptions\ExceptionHandlerData $exceptionHandlerData
     *
     * @return void
     */
    public function pushServiceErrorHandler(Handlers\Exceptions\ExceptionHandlerData $exceptionHandlerData): void
    {
        $this->errorHandlers->add($exceptionHandlerData);
    }

    /**
     * Apply service
     * Parse service handlers (command handler, event listener, error handler)
     *
     * @param ServiceInterface $service
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function applyService(ServiceInterface $service): void
    {
        $defaultServiceLoggerChannel = $this->serviceHandlersExtractor->extractServiceLoggerChannel($service);
        $handlers = $this->serviceHandlersExtractor->extractHandlers($service, $defaultServiceLoggerChannel);

        foreach($handlers as $type => $collection)
        {
            foreach($collection as $handlerData)
            {
                switch($type)
                {
                    case ServiceHandlersExtractorInterface::HANDLER_TYPE_ERRORS:
                        $this->pushServiceErrorHandler($handlerData);
                        break;

                    case ServiceHandlersExtractorInterface::HANDLER_TYPE_MESSAGES:
                        $this->pushMessageHandler($handlerData);
                        break;
                }
            }
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
        if(true === $this->isCompiled)
        {
            throw new MessageBusAlreadyCreatedException();
        }

        if(true === \array_key_exists(Behaviors\ErrorHandleBehavior::class, $this->behaviors))
        {
            $this->behaviors[Behaviors\ErrorHandleBehavior::class] = Behaviors\ErrorHandleBehavior::create(
                $this->errorHandlers
            );
        }

        $messageBus = MessageBus::build(
            $this->prepareTaskCollection(),
            $this->logger
        );

        $this->isCompiled = true;

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

        foreach($this->messageHandlers as $messageNamespace => $handlers)
        {
            if(false === \is_array($handlers))
            {
                $handlers = [$handlers];
            }

            foreach($handlers as $handlerData)
            {
                /** @var Handlers\Messages\MessageHandlerData $handlerData */

                $task = Task::new(
                    $handlerData->getMessageHandler(),
                    $handlerData->getExecutionOptions()
                );

                foreach($this->behaviors as $behavior)
                {
                    $task = $behavior->apply($task);
                }

                $collection->add(
                    MessageBusTask::create($handlerData->getMessageClassNamespace(), $task)
                );
            }
        }

        return $collection;
    }
}
