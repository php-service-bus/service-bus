<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services;

use ServiceBus\Common\MessageExecutor\MessageExecutorFactory;
use ServiceBus\Common\MessageHandler\MessageHandler;
use ServiceBus\MessagesRouter\Exceptions\MessageRouterConfigurationFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\MessagesRouter\RouterConfigurator;
use ServiceBus\Services\Configuration\ServiceHandlersLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 *
 */
final class MessagesRouterConfigurator implements RouterConfigurator
{
    /**
     * Message executor factory.
     *
     * @var MessageExecutorFactory
     */
    private $executorFactory;

    /**
     * List of registered sagas.
     *
     * @var array<mixed, string>
     */
    private $servicesList;

    /**
     * Isolated service locator for registered services.
     *
     * @var ServiceLocator
     */
    private $servicesServiceLocator;

    /**
     * Isolated service locator for routing configuration.
     *
     * @var ServiceLocator
     */
    private $routingServiceLocator;

    /**
     * @param array<mixed, string> $servicesList
     * @param ServiceLocator       $routingServiceLocator
     * @param ServiceLocator       $servicesServiceLocator
     */
    public function __construct(
        MessageExecutorFactory $executorFactory,
        array $servicesList,
        ServiceLocator $routingServiceLocator,
        ServiceLocator $servicesServiceLocator
    ) {
        $this->executorFactory        = $executorFactory;
        $this->servicesList           = $servicesList;
        $this->routingServiceLocator  = $routingServiceLocator;
        $this->servicesServiceLocator = $servicesServiceLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Router $router): void
    {
        try
        {
            /** @var ServiceHandlersLoader $serviceConfigurationExtractor */
            $serviceConfigurationExtractor = $this->routingServiceLocator->get(ServiceHandlersLoader::class);

            foreach ($this->servicesList as $serviceId)
            {
                /** @var object $serviceObject */
                $serviceObject = $this->servicesServiceLocator->get(\sprintf('%s_service', $serviceId));

                /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
                foreach ($serviceConfigurationExtractor->load($serviceObject) as $handler)
                {
                    self::assertMessageClassSpecifiedInArguments($serviceObject, $handler->messageHandler);

                    $messageExecutor = $this->executorFactory->create($handler->messageHandler);

                    $registerMethod = true === $handler->isCommandHandler()
                        ? 'registerHandler'
                        : 'registerListener';

                    $router->{$registerMethod}((string) $handler->messageHandler->messageClass, $messageExecutor);
                }
            }
        }
        catch (\Throwable $throwable)
        {
            throw new MessageRouterConfigurationFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param object         $service
     * @param MessageHandler $handler
     *
     * @throws \LogicException
     *
     * @return void
     */
    private static function assertMessageClassSpecifiedInArguments(object $service, MessageHandler $handler): void
    {
        if (null === $handler->messageClass || '' === (string) $handler->messageClass)
        {
            throw new \LogicException(
                \sprintf(
                    'The message argument was not found in the "%s:%s" method',
                    \get_class($service),
                    $handler->methodName
                )
            );
        }
    }
}
