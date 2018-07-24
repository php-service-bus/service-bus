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

namespace Desperado\ServiceBus\MessageBus\Processor;

use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\MessageBus\MessageHandler\ArgumentCollection;

/**
 * Command\event processor
 * Note: For sagas used SagaProcessor
 */
final class MessageProcessor implements Processor
{
    /**
     * Message handler
     *
     * @var \Closure
     */
    private $closure;

    /**
     * @var ArgumentCollection
     */
    private $arguments;

    /**
     * Argument resolvers collection
     *
     * @var array<string, \Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {

    }
}
