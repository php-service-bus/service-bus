<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Exceptions;

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * The context of sending messages is not set
 */
class OutboundContextNotAppliedException extends \LogicException implements ServiceBusExceptionInterface
{
    public function __construct()
    {
        parent::__construct(
            'You need to set the context for sending messages to the transport layer.'
        );
    }
}
