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
 * Interface marker of exceptions thrown during application initialization
 */
interface BootstrapExceptionInterface extends ServiceBusExceptionInterface
{

}
