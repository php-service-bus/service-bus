<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\ArgumentResolvers;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\MessageHandlers\HandlerArgument;
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
    public function supports(HandlerArgument $argument): bool
    {
        return true === $argument->isObject() && true === $this->serviceLocator->has((string) $argument->className());
    }

    /**
     * @inheritdoc
     *
     * @return object
     */
    public function resolve(Message $message, MessageDeliveryContext $context, HandlerArgument $argument): object
    {
        /** @var object $object */
        $object = $this->serviceLocator->get((string) $argument->className());

        return $object;
    }
}
