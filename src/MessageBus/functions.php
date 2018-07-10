<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus;

use function Amp\asyncCall;
use Desperado\ServiceBus\Kernel\ApplicationContext;
use Desperado\ServiceBus\MessageBus\Exceptions\NoMessageHandlersFound;
use Psr\Log\LogLevel;

/**
 * @param MessageBus $messageBus
 *
 * @return \Generator
 */
function messageDispatcher(MessageBus $messageBus): \Generator
{
    while(true)
    {
        /** @var ApplicationContext $context */
        $context = yield;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        asyncCall(
            static function(ApplicationContext $context) use ($messageBus): \Generator
            {
                try
                {
                    yield $messageBus->dispatch($context);
                }
                catch(NoMessageHandlersFound $exception)
                {
                    $context->logContextThrowable($exception, LogLevel::DEBUG);
                }
            },
            $context
        );
    }
}
