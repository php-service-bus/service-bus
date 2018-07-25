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

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\MessageBus\MessageHandler\Handler;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver;
use Desperado\ServiceBus\MessageBus\Processor\MessageProcessor;
use Desperado\ServiceBus\MessageBus\Processor\ProcessorsMap;
use Desperado\ServiceBus\MessageBus\Processor\ValidationMessageProcessor;
use Desperado\ServiceBus\Sagas\Configuration\SagaListenersLoader;
use Desperado\ServiceBus\Services\Configuration\ServiceHandlersLoader;

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

            $this->assertMessageClassSpecifiedInArguments($service, $handler);
            $this->assertUniqueCommandHandler($handler);

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

    /**
     * @param Handler $handler
     *
     * @return void
     *
     * @throws \LogicException
     */
    private function assertUniqueCommandHandler(Handler $handler): void
    {
        /** @var string $messageClass */
        $messageClass = $handler->messageClass();

        if(true === $handler->isCommandHandler() && true === $this->processorsList->hasTask($messageClass))
        {
            throw new \LogicException(
                \sprintf(
                    'The handler for the "%s" command has already been added earlier. You can not add multiple command handlers',
                    $messageClass
                )
            );
        }
    }

    /**
     * @param object  $service
     * @param Handler $handler
     *
     * @return void
     *
     * @throws \LogicException
     */
    private function assertMessageClassSpecifiedInArguments(object $service, Handler $handler): void
    {
        if(null === $handler->messageClass() || '' === (string) $handler->messageClass())
        {
            throw new \LogicException(
                \sprintf(
                    'In the method of "%s:%s" is not found an argument of type "%s"',
                    \get_class($service),
                    $handler->methodName(),
                    Message::class
                )
            );
        }
    }
}
