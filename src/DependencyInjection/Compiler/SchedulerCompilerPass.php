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
 * Setup scheduler storage
 */
class SchedulerCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @var string
     */
    private $schedulerStorageKey;

    /**
     * @param $schedulerStorageKey
     */
    public function __construct(string $schedulerStorageKey)
    {
        $this->schedulerStorageKey = $schedulerStorageKey;
    }

    /**
     * @inheritdoc
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        if(false === $container->has($this->schedulerStorageKey))
        {
            throw new \LogicException(
                \sprintf(
                    'Can not find service "%s" in the dependency container. The scheduler storage must be configured',
                    $this->schedulerStorageKey
                )
            );
        }

        $serviceDefinition = $container->getDefinition('service_bus.scheduler.provider');
        $serviceDefinition->setArgument(0, new DependencyInjection\Reference($this->schedulerStorageKey));
    }
}
