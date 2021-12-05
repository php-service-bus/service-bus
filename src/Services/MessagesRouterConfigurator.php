<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services;

use ServiceBus\Common\MessageExecutor\MessageExecutorFactory;
use ServiceBus\MessagesRouter\Exceptions\MessageRouterConfigurationFailed;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\MessagesRouter\RouterConfigurator;
use ServiceBus\Services\Configuration\ServiceHandlersLoader;
use ServiceBus\Services\Configuration\ServiceMessageHandlerType;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class MessagesRouterConfigurator implements RouterConfigurator
{
    /**
     * @var MessageExecutorFactory
     */
    private $executorFactory;

    /**
     * @psalm-var array<mixed, string>
     *
     * @var array
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
     * @psalm-param array<mixed, string> $servicesList
     */
    public function __construct(
        MessageExecutorFactory $executorFactory,
        array                  $servicesList,
        ServiceLocator         $routingServiceLocator,
        ServiceLocator         $servicesServiceLocator
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
                    $messageExecutor = $this->executorFactory->create($handler->messageHandler);

                    $registerMethod = $handler->type === ServiceMessageHandlerType::COMMAND_HANDLER
                        ? 'registerHandler'
                        : 'registerListener';

                    $router->{$registerMethod}($handler->messageHandler->messageClass, $messageExecutor);
                }
            }
        }
        catch (\Throwable $throwable)
        {
            throw new MessageRouterConfigurationFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }
}
