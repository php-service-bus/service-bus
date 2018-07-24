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

use Amp\Success;
use Doctrine\DBAL\Driver\Statement;
use Amp\Promise;
use Desperado\ServiceBus\Storage\ResultSet;

/**
 *
 */
final class DoctrineDBALResultSet implements ResultSet
{
    /**
     * Last row emitted
     *
     * @var array|null
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
     * @param Statement $wrappedStmt
     */
    public function __construct(Statement $wrappedStmt)
    {
        $this->fetchResult  = $wrappedStmt->fetchAll();
        $this->resultsCount = \count($this->fetchResult);
    }

    /**
     * @inheritdoc
     */
    public function advance(int $rowType = ResultSet::FETCH_ASSOC): Promise
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
    public function getCurrent()
    {
        if(null !== $this->currentRow)
        {
            return $this->currentRow;
        }

        return $this->currentRow = $this->fetchResult[$this->currentPosition - 1] ?? null;
    }
}
