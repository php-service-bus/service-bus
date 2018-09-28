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

namespace Desperado\ServiceBus\Sagas\Exceptions;

use Desperado\ServiceBus\Common\Exceptions\ServiceBusExceptionMarker;

/**
 *
 */
final class InvalidExpireDateInterval extends \InvalidArgumentException implements ServiceBusExceptionMarker
{

}
