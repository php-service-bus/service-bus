<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Exceptions;

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * The service configuration error marker
 */
interface ServiceConfigurationExceptionInterface extends ServiceBusExceptionInterface
{

}
