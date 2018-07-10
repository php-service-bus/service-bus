<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Exceptions;

use Desperado\Contracts\Common\Message;

/**
 * There are no handlers configured for the message
 */
final class NoMessageHandlersFound extends \RuntimeException
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
