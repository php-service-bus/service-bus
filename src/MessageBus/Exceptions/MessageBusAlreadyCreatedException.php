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

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * Message bus already created
 */
class MessageBusAlreadyCreatedException extends \LogicException implements ServiceBusExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Message bus already created');
    }
}
