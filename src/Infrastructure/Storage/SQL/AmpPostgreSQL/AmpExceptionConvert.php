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

namespace Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL;

use Amp\Postgres\QueryExecutionError;
use Amp\Sql\ConnectionException;
use Desperado\ServiceBus\Infrastructure\Storage\Exceptions as InternalExceptions;

/**
 * Convert library exceptions to internal types
 */
final class AmpExceptionConvert
{
    /**
     * Convert AmPHP exceptions
     *
     * @param \Throwable $throwable
     *
     * @return InternalExceptions\UniqueConstraintViolationCheckFailed|InternalExceptions\ConnectionFailed|InternalExceptions\StorageInteractingFailed
     */
    public static function do(\Throwable $throwable): \Throwable
    {
        if(
            $throwable instanceof QueryExecutionError &&
            true === \in_array((int) $throwable->getDiagnostics()['sqlstate'], [23503, 23505], true)
        )
        {
            return new InternalExceptions\UniqueConstraintViolationCheckFailed(
                $throwable->getMessage(),
                $throwable->getCode(),
                $throwable
            );
        }

        if($throwable instanceof ConnectionException)
        {
            return new InternalExceptions\ConnectionFailed(
                $throwable->getMessage(),
                $throwable->getCode(),
                $throwable
            );
        }

        return new InternalExceptions\StorageInteractingFailed(
            $throwable->getMessage(),
            $throwable->getCode(),
            $throwable
        );
    }

    /**
     * CLose constructor
     */
    private function __construct()
    {

    }
}
