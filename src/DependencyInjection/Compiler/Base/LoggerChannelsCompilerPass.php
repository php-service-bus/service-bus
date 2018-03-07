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

use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use Symfony\Component\DependencyInjection;

/**
 * Logger channels support
 */
final class LoggerChannelsCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        foreach($container->findTaggedServiceIds('service_bus.logger') as $id => $tags)
        {
            foreach($tags as $tag)
            {
                if(true === isset($tag['channel']) && '' !== $tag['channel'])
                {
                    $serviceDefinition = $container->getDefinition($id);
                    $loggerServiceId = $this->prepareLoggerService(
                        $container,
                        $container->getParameterBag()->resolveValue($tag['channel'])
                    );

                    $this->processReplaceArgument($serviceDefinition, $loggerServiceId);
                }
            }
        }
    }

    /**
     * Prepare logger service for specified channel
     *
     * @param DependencyInjection\ContainerBuilder $container
     * @param string                               $channel
     *
     * @return string
     */
    private function prepareLoggerService(DependencyInjection\ContainerBuilder $container, string $channel): string
    {
        $loggerServiceId = \sprintf('kernel.logger.%s', $channel);

        if(false === $container->hasDefinition($loggerServiceId))
        {
            $container->set($loggerServiceId, LoggerRegistry::getLogger($channel));
        }

        return $loggerServiceId;
    }

    /**
     * Replace service argument to specified logger
     *
     * @param DependencyInjection\Definition $serviceDefinition
     * @param string                         $loggerServiceId
     *
     * @return void
     */
    private function processReplaceArgument(DependencyInjection\Definition $serviceDefinition, string $loggerServiceId): void
    {
        foreach($serviceDefinition->getArguments() as $index => $argument)
        {
            if(
                true === ($argument instanceof DependencyInjection\Reference) &&
                ('logger' === (string) $argument || 'kernel.logger' === (string) $argument))
            {
                /** @var DependencyInjection\Reference $argument */

                $serviceDefinition->replaceArgument(
                    $index,
                    new DependencyInjection\Reference($loggerServiceId, $argument->getInvalidBehavior())
                );
            }
        }
    }
}
