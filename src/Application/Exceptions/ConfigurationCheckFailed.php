<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\Exceptions;

/**
 *
 */
final class ConfigurationCheckFailed extends \LogicException
{
    public static function emptyEntryPointName(): self
    {
        return new self('Entry point name must be specified');
    }
}
