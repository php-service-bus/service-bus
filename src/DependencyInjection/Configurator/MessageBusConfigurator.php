<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Configurator;

use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\Services\ServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Message bus configurator
 */
class MessageBusConfigurator
{
    /**
     * Message bus builder
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Services collection
     *
     * @var ServiceInterface[]
     */
    private $services;

    /**
     * @param MessageBusBuilder  $messageBusBuilder
     * @param ContainerInterface $container
     * @param array              $services
     */
    public function __construct(MessageBusBuilder $messageBusBuilder, ContainerInterface $container, array $services)
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->services = \array_map(
            function(string $id) use ($container)
            {
                return $container->get($id);
            },
            $services
        );
    }

    /**
     * Process service handlers configure
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function configure(): void
    {
        foreach($this->services as $service)
        {
            $this->messageBusBuilder->applyService($service);
        }
    }
}
