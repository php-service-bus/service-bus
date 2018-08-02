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

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed;
use Desperado\ServiceBus\Storage\ResultSet;
use Amp\Postgres\PgSqlResultSet;
use Amp\Postgres\PgSqlCommandResult;

/**
 *
 */
class AmpPostgreSQLResultSet implements ResultSet
{
    /**
     * @var PgSqlCommandResult|PgSqlResultSet
     */
    private $originalResultSet;

    /**
     * @noinspection PhpDocSignatureInspection
     *
     * @param PgSqlCommandResult|PgSqlResultSet $originalResultSet
     */
    public function __construct(object $originalResultSet)
    {
        $this->originalResultSet = $originalResultSet;
    }

    /**
     * @inheritdoc
     */
    public function advance(int $rowType = ResultSet::FETCH_ASSOC): Promise
    {
        try
        {
            if($this->originalResultSet instanceof PgSqlResultSet)
            {
                return $this->originalResultSet->advance($rowType);
            }

            return new Success(true);
        }
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCurrent()
    {
        try
        {
            if($this->originalResultSet instanceof PgSqlResultSet)
            {
                return $this->originalResultSet->getCurrent();
            }

            return null;
        }
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
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
        catch(\Throwable $throwable)
        {
            throw new ResultSetIterationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}
