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

use Amp\Success;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Storage\ResultSet;

/**
 *
 */
final class DoctrineDBALResultSet implements ResultSet
{
    /**
     * Last row emitted
     *
     * @var array<mixed, mixed>|null
     */
    private $currentRow;

    /**
     * Pdo fetch result
     *
     * @var array|null
     */
    private $fetchResult;

    /**
     * Results count
     *
     * @var int
     */
    private $resultsCount;

    /**
     * Current iterator position
     *
     * @var int
     */
    private $currentPosition = 0;

    /**
     * Connection instance
     *
     * @var Connection
     */
    private $connection;

    /**
     * Number of rows affected by the last DELETE, INSERT, or UPDATE statement
     *
     * @var int
     */
    private $affectedRows;

    /**
     * @param Connection $connection
     * @param Statement  $wrappedStmt
     */
    public function __construct(Connection $connection, Statement $wrappedStmt)
    {
        $this->connection   = $connection;
        $this->fetchResult  = $wrappedStmt->fetchAll();
        $this->affectedRows = $wrappedStmt->rowCount();
        $this->resultsCount = \count($this->fetchResult);
    }

    /**
     * @inheritdoc
     */
    public function advance(): Promise
    {
        $this->currentRow = null;

        if(++$this->currentPosition > $this->resultsCount)
        {
            return new Success(false);
        }

        return new Success(true);
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?array
    {
        if(null !== $this->currentRow)
        {
            return $this->currentRow;
        }

        /** @var array<mixed, mixed>|null $data */
        $data = $this->fetchResult[$this->currentPosition - 1] ?? null;

        return $this->currentRow = $data;
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId(?string $sequence = null): ?string
    {
        return $this->connection->lastInsertId($sequence);
    }

    /**
     * @inheritdoc
     */
    public function affectedRows(): int
    {
        return $this->affectedRows;
    }
}
