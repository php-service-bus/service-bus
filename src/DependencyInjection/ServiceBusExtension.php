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
        self::loadFromDirectory(__DIR__ . '/../Resources/config/base', $container);

        $this->loadCompilationPasses(
            $this->getCompilationPasses(), $container
        );
    }

    /**
     * Load application compilation passes
     *
     * @param array                                $compilationPasses
     * @param DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    private function loadCompilationPasses(array $compilationPasses, DependencyInjection\ContainerBuilder $container): void
    {
        foreach($compilationPasses as $compilationPass)
        {
            $container->addCompilerPass($compilationPass);
        }
    }

    /**
     * Get compiler passes
     *
     * @return DependencyInjection\Compiler\CompilerPassInterface[]
     */
    private function getCompilationPasses(): array
    {
        return [
            new ServiceBusDependencyInjection\Compiler\Base\LoggerChannelsCompilerPass(),
            new ServiceBusDependencyInjection\Compiler\Base\ModulesCompilerPass(),
            new ServiceBusDependencyInjection\Compiler\Base\ServicesCompilerPass(),
            new ServiceBusDependencyInjection\Compiler\Base\SchedulerCompilerPass(
                $this->bootstrapServicesDefinitions->getSchedulerStorageKey()
            ),
            new ServiceBusDependencyInjection\Compiler\Base\SagaStorageCompilerPass(
                $this->bootstrapServicesDefinitions->getSagaStorageKey()
            ),
            new ServiceBusDependencyInjection\Compiler\Base\EntryPointCompilerPass(
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
    }
}