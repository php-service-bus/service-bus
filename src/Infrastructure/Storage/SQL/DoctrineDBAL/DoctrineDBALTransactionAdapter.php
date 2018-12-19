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

use Doctrine\DBAL\Connection;
use function Amp\call;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter;

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
                /** @var string $message Driver-specific error message */
                $message = $this->connection->errorInfo()[2];

                throw new \RuntimeException($message);
                // @codeCoverageIgnoreEnd
            }

            return new Success(new DoctrineDBALResultSet($this->connection, $statement));
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

        /** InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function() use ($connection): void
            {
                try
                {
                    $connection->commit();
                }
                    // @codeCoverageIgnoreStart
                catch(\Throwable $throwable)
                {
                    throw DoctrineDBALExceptionConvert::do($throwable);
                }
                // @codeCoverageIgnoreEnd
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function rollback(): Promise
    {
        $connection = $this->connection;

        /** InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function() use ($connection): void
            {
                try
                {
                    $connection->rollBack();
                }
                    // @codeCoverageIgnoreStart
                catch(\Throwable $throwable)
                {
                    /** We will not throw an exception */
                }
                // @codeCoverageIgnoreEnd
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function unescapeBinary($payload): string
    {
        if(true === \is_resource($payload))
        {
            return \stream_get_contents($payload, -1, 0);
        }

        return $payload;
    }
}
