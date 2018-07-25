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

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\MessageBus\MessageHandler\HandlerArgumentCollection;

/**
 * Command\event processor
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
     * @var HandlerArgumentCollection
     */
    private $arguments;

    /**
     * Argument resolvers collection
     *
     * @var array<string, \Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * @param \Closure                  $closure
     * @param HandlerArgumentCollection $arguments
     * @param array                     $argumentResolvers
     */
    public function __construct(\Closure $closure, HandlerArgumentCollection $arguments, array $argumentResolvers)
    {
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->argumentResolvers = $argumentResolvers;
    }


    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            static function(Message $message, KernelContext $context): void
            {

            },
            $message, $context
        );
    }
}
