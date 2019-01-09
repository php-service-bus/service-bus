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

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageConfiguration;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param StorageConfiguration $configuration
     * @param LoggerInterface|null $logger
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function __construct(StorageConfiguration $configuration, ?LoggerInterface $logger = null)
    {
        try
        {
            /** @psalm-suppress InternalClass */
            $this->connection = DriverManager::getConnection(
                ['url' => $configuration->originalDSN],
                new Configuration()
            );

            $this->logger = $logger ?? new NullLogger();
        }
        catch(\Throwable $throwable)
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw DoctrineDBALExceptionConvert::do($throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(string $queryString, array $parameters = []): Promise
    {
        $this->logger->debug($queryString, $parameters);

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
            $this->logger->debug('START TRANSACTION');

            $this->connection->beginTransaction();

            return new Success(new DoctrineDBALTransactionAdapter($this->connection, $this->logger));
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            return new Failure(DoctrineDBALExceptionConvert::do($throwable));
        }
        // @codeCoverageIgnoreEnd
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
