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

use function Amp\call;
use Amp\Postgres\ConnectionConfig;
use function Amp\Postgres\pool;
use Amp\Postgres\Pool;
use Amp\Promise;
use Amp\Sql\Transaction;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageConfiguration;

/**
 *
 */
final class AmpPostgreSQLAdapter implements StorageAdapter
{
    /**
     * Connections pool
     *
     * @var Pool
     */
    private $pool;

    /**
     * @param StorageConfiguration $configuration
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function __construct(StorageConfiguration $configuration)
    {
        $queryData = $configuration->queryParameters();

        $maxConnectionsCount = (int) ($queryData['max_connections'] ?? Pool::DEFAULT_MAX_CONNECTIONS);
        $idleTimeout         = (int) ($queryData['idle_timeout'] ?? Pool::DEFAULT_IDLE_TIMEOUT);

        $this->pool = pool(
            new ConnectionConfig(
                $configuration->host(),
                $configuration->port() ?? ConnectionConfig::DEFAULT_PORT,
                $configuration->username(),
                $configuration->password(),
                $configuration->databaseName()
            ),
            $maxConnectionsCount,
            $idleTimeout

        );
    }

    public function __destruct()
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType Null in case of error */
        if(null !== $this->pool)
        {
            $this->pool->close();
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(string $queryString, array $parameters = []): Promise
    {
        $connectionsPool = $this->pool;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
        /** @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator */
            static function(string $queryString, array $parameters = []) use ($connectionsPool): \Generator
            {
                try
                {
                    return new AmpPostgreSQLResultSet(
                        yield $connectionsPool->execute($queryString, $parameters)
                    );
                }
                catch(\Throwable $throwable)
                {
                    throw AmpExceptionConvert::do($throwable);
                }
            },
            $queryString,
            $parameters
        );
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
        $connectionsPool = $this->pool;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
        /** @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator */
            static function() use ($connectionsPool): \Generator
            {
                try
                {
                    return new AmpPostgreSQLTransactionAdapter(
                        yield $connectionsPool->beginTransaction(Transaction::ISOLATION_COMMITTED)
                    );
                }
                    // @codeCoverageIgnoreStart
                catch(\Throwable $throwable)
                {
                    throw AmpExceptionConvert::do($throwable);
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
            $payload = \stream_get_contents($payload, -1, 0);
        }

        /** @var string $payload */

        /** @noinspection PhpComposerExtensionStubsInspection */
        return \pg_unescape_bytea($payload);
    }
}
