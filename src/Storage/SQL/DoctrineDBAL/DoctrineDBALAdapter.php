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

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageConfiguration;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * DoctrineDBAL adapter
 *
 * Designed primarily for testing. Please do not use this adapter in your code
 */
final class DoctrineDBALAdapter implements StorageAdapter
{
    /**
     * Doctrine connection
     *
     * @var Connection
     */
    private $connection;

    /**
     * @param StorageConfiguration $configuration
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function __construct(StorageConfiguration $configuration)
    {
        try
        {
            $this->connection = DriverManager::getConnection(
                ['url' => $configuration->originalDSN()],
                new Configuration()
            );
        }
        catch(\Throwable $throwable)
        {
            throw DoctrineDBALExceptionConvert::do($throwable);
        }
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
    public function supportsTransaction(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function transaction(): Promise
    {
        try
        {
            $this->connection->beginTransaction();

            return new Success(new DoctrineDBALTransactionAdapter($this->connection));
        }
        catch(\Throwable $throwable)
        {
            return new Failure(DoctrineDBALExceptionConvert::do($throwable));
        }
    }

    /**
     * @inheritdoc
     */
    public function unescapeBinary(string $string): string
    {
        return $string;
    }
}
