<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection;

/**
 * Entry point configuration
 */
final class EntryPointCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * Key under which the message transport service is described in the container
     *
     * @var string
     */
    private $transportContainerKey;

    /**
     * Key under which the kernel service is described in the container
     *
     * @var string
     */
    private $kernelContainerKey;

    /**
     * Key under which the context service is described in the container
     *
     * @var string
     */
    private $executionContextContainerKey;

    /**
     * @param string $transportContainerKey
     * @param string $kernelContainerKey
     * @param string $executionContextContainerKey
     */
    public function __construct(
        string $transportContainerKey,
        string $kernelContainerKey,
        string $executionContextContainerKey
    )
    {
        $this->transportContainerKey = $transportContainerKey;
        $this->kernelContainerKey = $kernelContainerKey;
        $this->executionContextContainerKey = $executionContextContainerKey;
    }
    
    /**
     * @inheritdoc
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $this->guardReferenceService($this->kernelContainerKey, $container);
        $this->guardReferenceService($this->executionContextContainerKey, $container);
        $this->guardReferenceService($this->transportContainerKey, $container);

        $definition = $container->getDefinition('service_bus.entry_point');
        $definition->setArgument(1, new DependencyInjection\Reference($this->kernelContainerKey));
        $definition->setArgument(2, new DependencyInjection\Reference($this->executionContextContainerKey));
        $definition->setArgument(3, new DependencyInjection\Reference($this->transportContainerKey));
    }

    /**
     * @param string                               $serviceKey
     * @param DependencyInjection\ContainerBuilder $container
     *
     * @return string
     */
    private function guardReferenceService(string $serviceKey, DependencyInjection\ContainerBuilder $container): string
    {
        if(false === $container->has($serviceKey))
        {
            throw new \LogicException(
                \sprintf(
                    'Can not find service "%s" in the dependency container', $serviceKey
                )
            );
        }

        return $serviceKey;
    }
}
