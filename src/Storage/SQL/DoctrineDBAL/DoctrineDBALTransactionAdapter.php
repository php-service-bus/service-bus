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

namespace Desperado\ServiceBus\Storage\SQL\DoctrineDBAL;

use Doctrine\DBAL\Connection;
use function Amp\call;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Storage\TransactionAdapter;

/**
 * Doctrine DBAL transaction adapter
 */
final class DoctrineDBALTransactionAdapter implements TransactionAdapter
{
    /**
     * DBAL connection
     *
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $queryString, array $parameters = []): Promise
    {
        try
        {
            $statement = $this->connection->prepare($queryString);
            $isSuccess = $statement->execute($parameters);

            if(false === $isSuccess)
            {
                // @codeCoverageIgnoreStart
                /** Driver-specific error message */
                throw new \RuntimeException($this->connection->errorInfo()[2]);
                // @codeCoverageIgnoreEnd
            }

            return new Success(new DoctrineDBALResultSet($statement));
        }
        catch(\Throwable $throwable)
        {
            return new Failure(DoctrineDBALExceptionConvert::do($throwable));
        }
    }

    /**
     * @inheritdoc
     */
    public function commit(): Promise
    {
        $connection = $this->connection;

        return call(
            static function() use ($connection): \Generator
            {
                try
                {
                    $connection->commit();

                    return yield new Success();
                }
                catch(\Throwable $throwable)
                {
                    throw DoctrineDBALExceptionConvert::do($throwable);
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function rollback(): Promise
    {
        $connection = $this->connection;

        return call(
            static function() use ($connection): \Generator
            {
                try
                {
                    $connection->rollBack();

                    return yield new Success();
                }
                catch(\Throwable $throwable)
                {
                    throw DoctrineDBALExceptionConvert::do($throwable);
                }
            }
        );
    }
}
