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

namespace Desperado\ServiceBus\Sagas\Configuration\Exceptions;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Exceptions\ServiceBusExceptionMarker;

/**
 * A property that contains an identifier was not found
 */
final class IdentifierFieldNotFound extends \RuntimeException implements ServiceBusExceptionMarker
{
    /**
     * @param Event  $event
     * @param string $property
     */
    public function __construct(Event $event, string $property)
    {
        parent::__construct(
            \sprintf(
                'A property that contains an identifier ("%s") was not found in class "%s"',
                $property,
                \get_class($event)
            )
        );
    }
}
