<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Compiler\Extensions;

use Symfony\Component\DependencyInjection;

/**
 * Http server configuration
 */
class HttpServerEntryPointCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
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
     * @param string $kernelContainerKey
     * @param string $executionContextContainerKey
     */
    public function __construct(
        string $kernelContainerKey,
        string $executionContextContainerKey
    )
    {
        $this->kernelContainerKey = $kernelContainerKey;
        $this->executionContextContainerKey = $executionContextContainerKey;
    }

    /**
     * @inheritdoc
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('service_bus.http_server.entry_point');
        $definition->setArgument(1, new DependencyInjection\Reference($this->kernelContainerKey));
        $definition->setArgument(2, new DependencyInjection\Reference($this->executionContextContainerKey));
    }
}
