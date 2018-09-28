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

namespace Desperado\ServiceBus\Storage;

use Amp\Promise;

/**
 *
 */
interface ResultSet
{
    public const FETCH_ARRAY  = 0;
    public const FETCH_ASSOC  = 1;
    public const FETCH_OBJECT = 2;

    /**
     * Succeeds with true if an emitted value is available by calling getCurrent() or false if the iterator has
     * resolved. If the iterator fails, the returned promise will fail with the same exception.
     *
     * @param int $rowType
     *
     * @return Promise
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed
     */
    public function advance(int $rowType = ResultSet::FETCH_ASSOC): Promise;

    /**
     * Gets the last emitted value or throws an exception if the iterator has completed
     *
     * @return mixed Value emitted from the iterator
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed
     */
    public function getCurrent();

    /**
     * Receive last insert id
     *
     * @param string $sequence
     *
     * @return string|int|null
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed
     */
    public function lastInsertId(?string $sequence = null);

    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed
     *
     * @return int
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ResultSetIterationFailed
     */
    public function affectedRows(): int;
}
