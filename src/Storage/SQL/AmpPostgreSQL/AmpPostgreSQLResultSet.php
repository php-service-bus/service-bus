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

namespace Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL;

use Amp\Postgres\PgSqlCommandResult;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed;
use Desperado\ServiceBus\Storage\ResultSet;
use Amp\Postgres\PgSqlResultSet;
use Amp\Sql\ResultSet as AmpResultSet;
use Amp\Postgres\PooledResultSet;

/**
 *
 */
class AmpPostgreSQLResultSet implements ResultSet
{
    /**
     * @var AmpResultSet|PooledResultSet
     */
    private $originalResultSet;

    /**
     * @noinspection   PhpDocSignatureInspection
     * @psalm-suppress TypeCoercion
     *
     * @param AmpResultSet|PooledResultSet $originalResultSet
     */
    public function __construct(object $originalResultSet)
    {
        $this->originalResultSet = $originalResultSet;
    }

    public function __destruct()
    {
        unset($this->originalResultSet);
    }

    /**
     * @inheritdoc
     */
    public function advance(): Promise
    {
        try
        {
            if($this->originalResultSet instanceof AmpResultSet)
            {
                return $this->originalResultSet->advance();
            }

            return new Success(false);
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?array
    {
        try
        {
            return $this->originalResultSet->getCurrent();
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId(?string $sequence = null): ?string
    {
        try
        {
            if($this->originalResultSet instanceof PgSqlResultSet)
            {
                $result = $this->originalResultSet->getCurrent();

                if(true === isset($result['id']))
                {
                    return (string) $result['id'];
                }
            }

            return null;
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @inheritdoc
     */
    public function affectedRows(): int
    {
        try
        {
            if($this->originalResultSet instanceof PgSqlCommandResult)
            {
                return $this->originalResultSet->getAffectedRowCount();
            }

            return 0;
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
        // @codeCoverageIgnoreEnd
    }
}
