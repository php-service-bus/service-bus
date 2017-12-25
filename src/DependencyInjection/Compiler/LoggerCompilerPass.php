<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\DependencyInjection\Compiler;

use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use Symfony\Component\DependencyInjection;

/**
 * Logger channels support
 */
class LoggerCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(DependencyInjection\ContainerBuilder $container)
    {
        foreach($container->findTaggedServiceIds('kernel.logger') as $id => $tags)
        {
            foreach($tags as $tag)
            {
                if(empty($tag['channel']))
                {
                    continue;
                }

                $resolvedChannel = $container->getParameterBag()->resolveValue($tag['channel']);

                $serviceDefinition = $container->getDefinition($id);
                $loggerServiceId = \sprintf('kernel.logger.%s', $resolvedChannel);

                if(false === $container->hasDefinition($loggerServiceId))
                {
                    $container->set($loggerServiceId, LoggerRegistry::getLogger($resolvedChannel));
                }

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
    }
}
