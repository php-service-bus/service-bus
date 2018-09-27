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
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\MessageBus\MessageHandler\Handler;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver;
use Desperado\ServiceBus\MessageBus\Processor\MessageProcessor;
use Desperado\ServiceBus\MessageBus\Processor\ProcessorsMap;
use Desperado\ServiceBus\MessageBus\Processor\ValidationMessageProcessor;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\SagaConfigurationLoader;
use Desperado\ServiceBus\Services\Configuration\ServiceHandlersLoader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Message bus builder
 */
final class MessageBusBuilder
{
    /**
     * @var SagaConfigurationLoader
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
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SagaConfigurationLoader $sagasConfigurationLoader
     * @param ServiceHandlersLoader   $servicesConfigurationLoader
     * @param SagaProvider           $sagaProvider
     * @param LoggerInterface|null    $logger
     */
    public function __construct(
        SagaConfigurationLoader $sagasConfigurationLoader,
        ServiceHandlersLoader $servicesConfigurationLoader,
        SagaProvider $sagaProvider,
        LoggerInterface $logger = null
    )
    {
        $this->sagasConfigurationLoader = $sagasConfigurationLoader;
        $this->servicesConfigurationLoader = $servicesConfigurationLoader;
        $this->sagaProvider = $sagaProvider;
        $this->logger = $logger ?? new NullLogger();

        $this->processorsList = new ProcessorsMap();
    }

    /**
     * Add saga listeners to messages bus
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param string           $sagaClass
     * @param ArgumentResolver ...$argumentResolvers
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addSaga(string $sagaClass, ArgumentResolver ... $argumentResolvers): void
    {
        /** @var \Desperado\ServiceBus\Sagas\Configuration\SagaConfiguration $sagaConfiguration */
        $sagaConfiguration = $this->sagasConfigurationLoader->load($sagaClass);

        foreach($sagaConfiguration->handlerCollection() as $handler)
        {
            /** @var \Desperado\ServiceBus\MessageBus\MessageHandler\Handler $handler */
            $this->processorsList->push(
                (string) $handler->messageClass(),
                new MessageProcessor($handler->toClosure(), $handler->arguments(), $argumentResolvers)
            );
        }


        invokeReflectionMethod(
            $this->sagaProvider,
            'appendMetaData',
            $sagaClass,
            $sagaConfiguration->metaData()
        );
    }

    /**
     * Add service messages (command\event) handlers
     *
     * @noinspection PhpDocSignatureInspection
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
     * Build message bus
     *
     * @return MessageBus
     */
    public function compile(): MessageBus
    {
        $this->logger->info(
            'The message bus has been successfully configured. "{registeredHandlersCount}" handlers registered', [
                'registeredHandlersCount' => \count($this->processorsList)
            ]
        );

        return new MessageBus($this->processorsList);
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
