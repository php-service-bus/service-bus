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

namespace Desperado\ServiceBus\Sagas\Exceptions\Processor;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Exceptions\ServiceBusExceptionMarker;

/**
 * The method of obtaining the identifier was not found
 */
final class ReceiveIdMethodNotFound extends \RuntimeException implements ServiceBusExceptionMarker
{
    /**
     * @param Event $event
     * @param array $choices
     */
    public function __construct(Event $event, array $choices)
    {
        parent::__construct(
            \sprintf(
                'The method of obtaining the identifier (variants: %s) was not found in the event event class "%s"',
                \implode(', ', \array_values($choices)),
                \get_class($event)
            )
        );
    }
}
