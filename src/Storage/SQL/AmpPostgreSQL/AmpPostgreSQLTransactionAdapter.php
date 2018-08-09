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

use Amp\Postgres\Transaction as AmpTransaction;
use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Storage\TransactionAdapter;

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
     * @param AmpTransaction $transaction
     */
    public function __construct(AmpTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function __destruct()
    {
        $this->transaction->close();
    }

    /**
     * @inheritdoc
     */
    public function execute(string $queryString, array $parameters = []): Promise
    {
        $transaction = $this->transaction;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(string $queryString, array $parameters = []) use ($transaction): \Generator
            {
                try
                {
                    /** @var \Amp\Postgres\Statement $statement */
                    $statement = yield $transaction->prepare($queryString);

                    /** @psalm-suppress UndefinedClass Class or interface Amp\Postgres\TupleResult does not exist */
                    $result = new AmpPostgreSQLResultSet(
                        yield $statement->execute($parameters)
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
    public function commit(): Promise
    {
        $transaction = $this->transaction;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function() use ($transaction): \Generator
            {
                try
                {
                    yield $transaction->commit();

                    $transaction->close();
                }
                catch(\Throwable $throwable)
                {
                    throw AmpExceptionConvert::do($throwable);
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function rollback(): Promise
    {
        $transaction = $this->transaction;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function() use ($transaction): \Generator
            {
                try
                {
                    return yield $transaction->rollback();
                }
                catch(\Throwable $throwable)
                {
                    throw AmpExceptionConvert::do($throwable);
                }
            }
        );
    }
}
