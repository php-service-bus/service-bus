<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Compiler\Base;

use Symfony\Component\DependencyInjection;

/**
 * Saga storage configuration
 */
final class SagaStorageCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * Key under which the saga storage service is described in the container
     *
     * @var string
     */
    private $sagaStorageContainerKey;

    /**
     * @param string $sagaStorageContainerKey
     */
    public function __construct(string $sagaStorageContainerKey)
    {
        $this->sagaStorageContainerKey = $sagaStorageContainerKey;
    }

    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        if(false === $container->has($this->sagaStorageContainerKey))
        {
            throw new \LogicException(
                \sprintf(
                    'Can not find service "%s" in the dependency container. The saga store must be configured',
                    $this->sagaStorageContainerKey
                )
            );
        }

        $definition = $container->getDefinition('service_bus.sagas.store');
        $definition->setArgument(0, new DependencyInjection\Reference($this->sagaStorageContainerKey));
    }
}
