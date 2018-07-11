<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Router\Exceptions;

/**
 *
 */
final class MessageClassCantBeEmpty extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Message class must be specified');
    }
}
