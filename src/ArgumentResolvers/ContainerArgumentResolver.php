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

use ServiceBus\Common\Messages\Message;
use ServiceBus\Context\KernelContext;
use ServiceBus\MessageHandlers\HandlerArgument;
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
    public function resolve(Message $message, KernelContext $context, HandlerArgument $argument): object
    {
        /** @var object $object */
        $object = $this->serviceLocator->get((string) $argument->className());

        return $object;
    }
}
