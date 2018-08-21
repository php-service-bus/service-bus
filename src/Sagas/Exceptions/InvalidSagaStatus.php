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
 * Incorrect saga status indicated
 */
class InvalidSagaStatus extends \InvalidArgumentException implements ServiceBusExceptionMarker
{
    /**
     * @param string $status
     */
    public function __construct(string $status)
    {
        parent::__construct(
            \sprintf('Incorrect saga status specified: %s', $status)
        );
    }
}
