<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Storage\Doctrine;

use Doctrine\DBAL\Exception as DoctrineDBALExceptions;
use Desperado\ServiceBus\Storage\Exceptions as StorageExceptions;

/**
 * Convert Doctrine exceptions to application-level
 */
final class DoctrineExceptionConverter
{
    /**
     * Convert Doctrine DBAL exceptions
     *
     * @param \Throwable $throwable
     *
     * @return StorageExceptions\StorageException
     */
    public static function convert(\Throwable $throwable): StorageExceptions\StorageException
    {
        if($throwable instanceof DoctrineDBALExceptions\ConnectionException)
        {
            return new StorageExceptions\StorageConnectionException($throwable->getMessage(), 0, $throwable);
        }

        if($throwable instanceof DoctrineDBALExceptions\UniqueConstraintViolationException)
        {
            return new StorageExceptions\UniqueConstraintViolationException($throwable->getMessage(), 0, $throwable);
        }

        return new StorageExceptions\StorageException($throwable->getMessage(), 0, $throwable);
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
