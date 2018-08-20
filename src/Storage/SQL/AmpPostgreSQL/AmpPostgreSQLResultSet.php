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
use Amp\Sql\PooledResultSet;
use Amp\Success;
use Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed;
use Desperado\ServiceBus\Storage\ResultSet;
use Amp\Postgres\PgSqlResultSet;
use Amp\Sql\ResultSet as AmpResultSet;

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
     * @noinspection PhpDocSignatureInspection
     *
     * @param AmpResultSet|PooledResultSet $originalResultSet
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
            if($this->originalResultSet instanceof AmpResultSet)
            {
                return $this->originalResultSet->advance($rowType);
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
    public function getCurrent()
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
}
