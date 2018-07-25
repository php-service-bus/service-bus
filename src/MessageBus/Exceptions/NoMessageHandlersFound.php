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

namespace Desperado\ServiceBus\MessageBus\Exceptions;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\Exceptions\ServiceBusExceptionMarker;

/**
 * There are no handlers configured for the message
 */
final class NoMessageHandlersFound extends \InvalidArgumentException implements ServiceBusExceptionMarker
{
    /**
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        parent::__construct(
            \sprintf(
                'There are no handlers configured for the message "%s"',
                \get_class($message)
            )
        );
    }
}
