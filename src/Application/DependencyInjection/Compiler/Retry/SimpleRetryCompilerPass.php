<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Application\DependencyInjection\Compiler\Retry;

use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\MessageSerializer\ObjectSerializer;
use ServiceBus\Retry\SimpleRetryStrategy;
use ServiceBus\Storage\Common\DatabaseAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class SimpleRetryCompilerPass implements CompilerPassInterface
{
    /**
     * @psalm-var positive-int
     *
     * @var int
     */
    private $maxRetryCount;

    /**
     * @psalm-var positive-int
     *
     * @var int
     */
    private $retryDelay;

    /**
     * @psalm-param positive-int $maxRetryCount
     * @psalm-param positive-int $retryDelay
     */
    public function __construct(int $maxRetryCount, int $retryDelay)
    {
        $this->maxRetryCount = $maxRetryCount;
        $this->retryDelay    = $retryDelay;
    }

    public function process(ContainerBuilder $container): void
    {
        $this->checkConfiguration($container);
        $this->injectParameters($container);

        $definition = new Definition(SimpleRetryStrategy::class, [
            new Reference(DatabaseAdapter::class),
            new Reference(ObjectSerializer::class),
            '%service_bus.retry.simple.max_retry_count%',
            '%service_bus.retry.simple.retry_delay%'
        ]);

        $container->setDefinition(RetryStrategy::class, $definition);
    }

    private function injectParameters(ContainerBuilder $containerBuilder): void
    {
        $parameters = [
            'service_bus.retry.simple.max_retry_count' => $this->maxRetryCount,
            'service_bus.retry.simple.retry_delay'     => $this->retryDelay
        ];

        foreach ($parameters as $key => $value)
        {
            $containerBuilder->setParameter(
                name: $key,
                value: $value
            );
        }
    }

    private function checkConfiguration(ContainerBuilder $container): void
    {
        /** @noinspection ClassConstantCanBeUsedInspection */
        if (
            \interface_exists('ServiceBus\Storage\Common\DatabaseAdapter') === false ||
            $container->has(DatabaseAdapter::class) === false
        ) {
            throw new \LogicException('Module `php-service-bus/storage` must be installed and loaded');
        }
    }
}
