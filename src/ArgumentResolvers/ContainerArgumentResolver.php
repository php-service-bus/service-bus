<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\ArgumentResolvers;

use Psr\Container\ContainerInterface;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageHandler\MessageHandlerArgument;

/**
 *
 */
final class ContainerArgumentResolver implements ArgumentResolver
{
    /**
     * @var ContainerInterface
     */
    private $serviceLocator;

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function supports(MessageHandlerArgument $argument): bool
    {
        return $argument->isObject && $this->serviceLocator->has((string) $argument->typeClass);
    }

    public function resolve(object $message, ServiceBusContext $context, MessageHandlerArgument $argument): object
    {
        /** @var object $object */
        $object = $this->serviceLocator->get((string) $argument->typeClass);

        return $object;
    }
}
