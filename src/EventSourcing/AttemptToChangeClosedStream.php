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

namespace Desperado\ServiceBus\EventSourcing;

use Desperado\ServiceBus\Common\Exceptions\ServiceBusExceptionMarker;

/**
 * It is not allowed to modify the closed event stream
 */
class AttemptToChangeClosedStream extends \RuntimeException implements ServiceBusExceptionMarker
{
    /**
     * @param AggregateId $id
     */
    public function __construct(AggregateId $id)
    {
        parent::__construct(
            \sprintf(
                'Can not add an event to a closed thread. Aggregate: "%s:%s"',
                $id, \get_class($id)
            )
        );
    }
}
