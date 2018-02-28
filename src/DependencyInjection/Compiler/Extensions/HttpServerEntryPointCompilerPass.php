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
     * Key under which the context is stored in the container for executing messages received via http
     *
     * @var string
     */
    private $httpContextContainerKey;

    /**
     * @param string $kernelContainerKey
     * @param string $httpContextContainerKey
     */
    public function __construct(
        string $kernelContainerKey,
        string $httpContextContainerKey
    )
    {
        $this->kernelContainerKey = $kernelContainerKey;
        $this->httpContextContainerKey = $httpContextContainerKey;
    }

    /**
     * @inheritdoc
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('service_bus.http_server.entry_point');
        $definition->setArgument(1, new DependencyInjection\Reference($this->kernelContainerKey));
        $definition->setArgument(2, new DependencyInjection\Reference($this->httpContextContainerKey));
    }
}
