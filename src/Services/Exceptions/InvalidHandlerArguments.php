<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Services\Exceptions;

final class InvalidHandlerArguments extends \InvalidArgumentException
{
    public static function emptyArguments(): self
    {
        return new self('The event handler must have at least 2 arguments: the message object (the first argument) and the context');
    }

    public static function invalidFirstArgument(): self
    {
        return new self('The first argument to the message handler must be the message object');
    }
}
