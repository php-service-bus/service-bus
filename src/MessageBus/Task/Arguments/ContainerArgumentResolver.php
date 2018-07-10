<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Task\Arguments;

use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Kernel\ApplicationContext;
use Desperado\ServiceBus\MessageBus\Configuration\MessageHandlerArgument;
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
    public function supports(MessageHandlerArgument $argument): bool
    {
        return true === $argument->isObject() && true === $this->container->has((string) $argument->className());
    }

    /**
     * @inheritdoc
     *
     * @return object
     */
    public function resolve(
        Message $message,
        ApplicationContext $applicationContext,
        MessageHandlerArgument $argument
    ): object
    {
        return $this->container->get((string) $argument->className());
    }
}
