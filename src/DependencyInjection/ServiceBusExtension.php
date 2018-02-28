<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection;

use Desperado\ServiceBus\Application\Bootstrap\BootstrapServicesDefinitions;
use Desperado\ServiceBus\DependencyInjection as ServiceBusDependencyInjection;
use Symfony\Component\DependencyInjection;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

/**
 * Share extensions
 */
final class ServiceBusExtension extends DependencyInjection\Extension\Extension
{
    use ServiceBusDependencyInjection\Traits\LoadServicesTrait;

    /**
     * @var BootstrapServicesDefinitions
     */
    private $bootstrapServicesDefinitions;

    /**
     * @param BootstrapServicesDefinitions $bootstrapServicesDefinitions
     */
    public function __construct(BootstrapServicesDefinitions $bootstrapServicesDefinitions)
    {
        $this->bootstrapServicesDefinitions = $bootstrapServicesDefinitions;
    }

    /**
     * @inheritDoc
     */
    public function load(array $configs, DependencyInjection\ContainerBuilder $container)
    {
        $compilationPasses = [
            new ServiceBusDependencyInjection\Compiler\LoggerChannelsCompilerPass(),
            new ServiceBusDependencyInjection\Compiler\ModulesCompilerPass(),
            new ServiceBusDependencyInjection\Compiler\ServicesCompilerPass(),
            new ServiceBusDependencyInjection\Compiler\SchedulerCompilerPass(
                $this->bootstrapServicesDefinitions->getSchedulerStorageKey()
            ),
            new ServiceBusDependencyInjection\Compiler\SagaStorageCompilerPass(
                $this->bootstrapServicesDefinitions->getSagaStorageKey()
            ),
            new ServiceBusDependencyInjection\Compiler\EntryPointCompilerPass(
                $this->bootstrapServicesDefinitions->getMessageTransportKey(),
                $this->bootstrapServicesDefinitions->getKernelKey(),
                $this->bootstrapServicesDefinitions->getApplicationContextKey()
            ),
            new RegisterListenersPass(
                'service_bus.event_dispatcher',
                'service_bus.event_listener',
                'service_bus.event_subscriber'
            )
        ];

        self::loadFromDirectory(__DIR__ . '/../Resources/config/base', $container);

        foreach($compilationPasses as $compilationPass)
        {
            $container->addCompilerPass($compilationPass);
        }
    }
}