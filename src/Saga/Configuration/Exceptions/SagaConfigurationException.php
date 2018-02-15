<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Configuration\Exceptions;

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 *
 */
class SagaConfigurationException extends \LogicException implements ServiceBusExceptionInterface
{

}
