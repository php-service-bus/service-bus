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

namespace Desperado\ServiceBus\MessageBus;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\MessageBus\Exceptions\NoMessageHandlersFound;
use Desperado\ServiceBus\MessageBus\Processor\ProcessorsMap;

/**
 *
 */
final class MessageBus
{
    /**
     * List of tasks for processing messages
     *
     * @var ProcessorsMap
     */
    private $processorsList;

    /**
     * @param ProcessorsMap $processorsList
     */
    public function __construct(ProcessorsMap $processorsList)
    {
        $this->processorsList = $processorsList;
    }

    /**
     * @param KernelContext $context
     *
     * @return Promise
     */
    public function dispatch(KernelContext $context): Promise
    {
        $processorsList = $this->processorsList;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(KernelContext $context) use ($processorsList): \Generator
            {
                $message      = $context->incomingEnvelope()->denormalized();
                $messageClass = \get_class($message);

                if(false === $processorsList->hasTask(\get_class($message)))
                {
                    throw new NoMessageHandlersFound($message);
                }

                foreach($processorsList->map($messageClass) as $task)
                {
                    /** @var \Desperado\ServiceBus\MessageBus\Processor\Processor $task */

                    yield $task($message, $context);
                }

                unset($message, $messageClass);
            },
            $context
        );
    }
}
