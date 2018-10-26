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

namespace Desperado\ServiceBus\EventSourcing\Exceptions;

use Desperado\ServiceBus\EventSourcing\AggregateId;

/**
 * It is not allowed to modify the closed event stream
 */
class AttemptToChangeClosedStream extends \RuntimeException
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
