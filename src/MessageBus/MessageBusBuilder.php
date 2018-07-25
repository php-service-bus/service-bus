<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus;

use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver;
use Desperado\ServiceBus\MessageBus\Processor\MessageProcessor;
use Desperado\ServiceBus\MessageBus\Processor\ProcessorsMap;
use Desperado\ServiceBus\MessageBus\Processor\ValidationMessageProcessor;
use Desperado\ServiceBus\Sagas\Configuration\SagaListenersLoader;
use Desperado\ServiceBus\Services\ServiceHandlersLoader;

/**
 * Message bus builder
 */
final class MessageBusBuilder
{
    /**
     * @var SagaListenersLoader
     */
    private $sagasConfigurationLoader;

    /**
     * @var ServiceHandlersLoader
     */
    private $servicesConfigurationLoader;

    /**
     * List of tasks for processing messages
     *
     * @var ProcessorsMap
     */
    private $processorsList;

    /**
     * @param SagaListenersLoader   $sagasConfigurationLoader
     * @param ServiceHandlersLoader $servicesConfigurationLoader
     */
    public function __construct(SagaListenersLoader $sagasConfigurationLoader, ServiceHandlersLoader $servicesConfigurationLoader)
    {
        $this->sagasConfigurationLoader    = $sagasConfigurationLoader;
        $this->servicesConfigurationLoader = $servicesConfigurationLoader;

        $this->processorsList = new ProcessorsMap();
    }

    /**
     * Add saga listeners to messages bus
     *
     * @param string $sagaClass
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addSaga(string $sagaClass, ArgumentResolver ... $argumentResolvers): void
    {
        foreach($this->sagasConfigurationLoader->load($sagaClass) as $handler)
        {
            /** @var \Desperado\ServiceBus\MessageBus\MessageHandler\Handler $handler */
            $this->processorsList->push(
                (string) $handler->messageClass(),
                new MessageProcessor($handler->toClosure(), $handler->arguments(), $argumentResolvers)
            );
        }
    }

    /**
     * Add service messages (command\event) handlers
     *
     * @param object           $service
     * @param ArgumentResolver ...$argumentResolvers
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addService(object $service, ArgumentResolver ... $argumentResolvers): void
    {
        foreach($this->servicesConfigurationLoader->load($service) as $handler)
        {
            /** @var \Desperado\ServiceBus\MessageBus\MessageHandler\Handler $handler */

            $messageProcessor = new MessageProcessor(
                $handler->toClosure($service),
                $handler->arguments(),
                $argumentResolvers
            );

            if(true === $handler->options()->validationEnabled())
            {
                $messageProcessor = new ValidationMessageProcessor(
                    $messageProcessor, $handler->options()->validationGroups()
                );
            }

            $this->processorsList->push((string) $handler->messageClass(), $messageProcessor);
        }
    }
}
