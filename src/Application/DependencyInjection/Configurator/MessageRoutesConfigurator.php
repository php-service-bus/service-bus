<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\DependencyInjection\Configurator;

use http\Message;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\MessageExecutor\MessageValidationExecutor;
use ServiceBus\MessageHandlers\Handler;
use ServiceBus\MessageRouter\Router;
use ServiceBus\Services\Configuration\ServiceHandlersLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 *
 */
final class MessageRoutesConfigurator
{
    /**
     * List of registered sagas
     *
     * @var array<mixed, string>
     */
    private $servicesList;

    /**
     * List of registered services
     *
     * @var array<mixed, string>
     */
    private $sagasList;

    /**
     * @var array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * Isolated service locator for registered services
     *
     * @var ServiceLocator
     */
    private $servicesServiceLocator;

    /**
     * Isolated service locator for routing configuration
     *
     * @var ServiceLocator
     */
    private $routingServiceLocator;

    /**
     * @param array<mixed, string>                                                    $servicesList
     * @param array<mixed, string>                                                    $sagasList
     * @param ServiceLocator                                                          $routingServiceLocator
     * @param ServiceLocator                                                          $servicesServiceLocator
     * @param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     */
    public function __construct(
        array $servicesList,
        array $sagasList,
        ServiceLocator $routingServiceLocator,
        ServiceLocator $servicesServiceLocator,
        array $argumentResolvers
    )
    {
        $this->servicesList           = $servicesList;
        $this->sagasList              = $sagasList;
        $this->routingServiceLocator  = $routingServiceLocator;
        $this->servicesServiceLocator = $servicesServiceLocator;
        $this->argumentResolvers      = $argumentResolvers;
    }

    /**
     * @param Router $router
     *
     * @return void
     *
     * @throws \Throwable Invalid handler definition
     */
    public function configure(Router $router): void
    {
        $this->registerServices($router);
    }

    /**
     * @param Router $router
     *
     * @return void
     *
     * @throws \Throwable Invalid handler definition
     */
    private function registerServices(Router $router): void
    {
        $validator = (new ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();

        /** @var ServiceHandlersLoader $serviceConfigurationExtractor */
        $serviceConfigurationExtractor = $this->routingServiceLocator->get(ServiceHandlersLoader::class);

        foreach($this->servicesList as $serviceId)
        {
            /** @var object $serviceObject */
            $serviceObject = $this->servicesServiceLocator->get(\sprintf('%s_service', $serviceId));

            /** @var \ServiceBus\MessageHandlers\Handler $handler */
            foreach($serviceConfigurationExtractor->load($serviceObject) as $handler)
            {
                self::assertMessageClassSpecifiedInArguments($serviceObject, $handler);

                $messageExecutor = new DefaultMessageExecutor(
                    $handler->toClosure($serviceObject),
                    $handler->arguments(),
                    $handler->options(),
                    $this->argumentResolvers
                );

                if(true === $handler->options()->validationEnabled())
                {
                    $messageExecutor = new MessageValidationExecutor($messageExecutor, $handler->options(), $validator);
                }

                $registerMethod = true === $handler->isCommandHandler()
                    ? 'registerHandler'
                    : 'registerListener';

                $router->{$registerMethod}((string) $handler->messageClass(), $messageExecutor);
            }
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
    private static function assertMessageClassSpecifiedInArguments(object $service, Handler $handler): void
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
