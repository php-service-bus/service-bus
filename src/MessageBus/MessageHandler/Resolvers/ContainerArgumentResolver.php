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

namespace Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\MessageBus\MessageHandler\HandlerArgument;
use Psr\Container\ContainerInterface;

/**
 *
 */
final class ContainerArgumentResolver implements ArgumentResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function supports(HandlerArgument $argument): bool
    {
        return true === $argument->isObject() && true === $this->container->has((string) $argument->className());
    }

    /**
     * @inheritdoc
     *
     * @return object
     */
    public function resolve(Message $message, MessageDeliveryContext $context, HandlerArgument $argument): object
    {
        return $this->container->get((string) $argument->className());
    }
}
