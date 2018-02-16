<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Processor\Exceptions;

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * Incorrect saga id
 */
class InvalidSagaIdentifierException extends \LogicException implements ServiceBusExceptionInterface
{

}
