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

use Amp\Postgres\Transaction as AmpTransaction;
use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter;
use Psr\Log\LoggerInterface;

/**
 *  Async PostgreSQL transaction adapter
 */
final class AmpPostgreSQLTransactionAdapter implements TransactionAdapter
{
    /**
     * Original transaction object
     *
     * @var AmpTransaction
     */
    private $transaction;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AmpTransaction  $transaction
     * @param LoggerInterface $logger
     */
    public function __construct(AmpTransaction $transaction, LoggerInterface $logger)
    {
        $this->transaction = $transaction;
        $this->logger      = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $queryString, array $parameters = []): Promise
    {
        $transaction = $this->transaction;
        $logger      = $this->logger;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
        /** @psalm-return AmpPostgreSQLResultSet */
            static function(string $queryString, array $parameters = []) use ($transaction, $logger): \Generator
            {
                try
                {
                    $logger->debug($queryString, $parameters);

                    return new AmpPostgreSQLResultSet(
                        yield $transaction->execute($queryString, $parameters)
                    );
                }
                    // @codeCoverageIgnoreStart
                catch(\Throwable $throwable)
                {
                    throw AmpExceptionConvert::do($throwable);
                }
                // @codeCoverageIgnoreEnd
            },
            $queryString,
            $parameters
        );
    }

    /**
     * @inheritdoc
     */
    public function commit(): Promise
    {
        $transaction = $this->transaction;
        $logger      = $this->logger;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function() use ($transaction, $logger): \Generator
            {
                try
                {
                    $logger->debug('COMMIT');

                    yield $transaction->commit();

                    $transaction->close();
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
    public function rollback(): Promise
    {
        $transaction = $this->transaction;
        $logger      = $this->logger;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function() use ($transaction, $logger): \Generator
            {
                try
                {
                    $logger->debug('ROLLBACK');

                    yield $transaction->rollback();

                    unset($transaction);
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
            $payload = \stream_get_contents($payload, -1, 0);
        }

        /** @var string $payload */

        /** @noinspection PhpComposerExtensionStubsInspection */
        return \pg_unescape_bytea($payload);
    }
}
