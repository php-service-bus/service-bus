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

namespace Desperado\ServiceBus\Infrastructure\Storage\SQL\DoctrineDBAL;

use Desperado\ServiceBus\Infrastructure\Storage\Exceptions as InternalExceptions;
use Doctrine\DBAL\Exception as DoctrineDBALExceptions;

/**
 * Convert library exceptions to internal types
 */
final class DoctrineDBALExceptionConvert
{
    /**
     * Convert Doctrine DBAL exceptions
     *
     * @param \Throwable $throwable
     *
     * @return InternalExceptions\ConnectionFailed|InternalExceptions\UniqueConstraintViolationCheckFailed|InternalExceptions\StorageInteractingFailed
     */
    public static function do(\Throwable $throwable): \Throwable
    {
        $message = \str_replace(\PHP_EOL, '', $throwable->getMessage());

        if($throwable instanceof DoctrineDBALExceptions\ConnectionException)
        {
            return new InternalExceptions\ConnectionFailed($message, $throwable->getCode(), $throwable);
        }

        if($throwable instanceof DoctrineDBALExceptions\UniqueConstraintViolationException)
        {
            return new InternalExceptions\UniqueConstraintViolationCheckFailed($message, $throwable->getCode(), $throwable);
        }

        return new InternalExceptions\StorageInteractingFailed($message, $throwable->getCode(), $throwable);
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}

