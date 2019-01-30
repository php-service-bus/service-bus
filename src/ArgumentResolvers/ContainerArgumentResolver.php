<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\ArgumentResolvers;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Messages\Message;
use ServiceBus\Common\MessageHandler\MessageHandlerArgument;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 *
 */
final class ContainerArgumentResolver implements ArgumentResolver
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @param ServiceLocator $serviceLocator
     */
    public function __construct(ServiceLocator $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @inheritdoc
     */
    public function supports(MessageHandlerArgument $argument): bool
    {
        return true === $argument->isObject && true === $this->serviceLocator->has((string) $argument->typeClass);
    }

    /**
     * @inheritdoc
     *
     * @return object
     */
    public function resolve(Message $message, ServiceBusContext $context, MessageHandlerArgument $argument): object
    {
        /** @var object $object */
        $object = $this->serviceLocator->get((string) $argument->typeClass);

        return $object;
    }
}
