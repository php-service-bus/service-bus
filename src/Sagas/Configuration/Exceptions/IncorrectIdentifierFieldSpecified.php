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

/**
 * A property that contains an identifier was not found
 */
final class IncorrectIdentifierFieldSpecified extends \RuntimeException
{
    /**
     * @param Event  $event
     * @param string $property
     *
     * @return self
     */
    public static function notFound(Event $event, string $property): self
    {
        return new static(
            \sprintf(
                'A property that contains an identifier ("%s") was not found in class "%s"',
                $property,
                \get_class($event)
            )
        );
    }

    /**
     * @param Event  $event
     * @param string $property
     *
     * @return self
     */
    public static function empty(Event $event, string $property): self
    {
        return new static(
            \sprintf(
                'The value of the "%s" property of the "%s" event can not be empty, since it is the saga id',
                $property,
                \get_class($event)
            )
        );
    }
}
