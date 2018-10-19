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

namespace Desperado\ServiceBus\Infrastructure\Storage;

use Amp\Promise;

/**
 * The result of the operation
 */
interface ResultSet
{
    /**
     * Succeeds with true if an emitted value is available by calling getCurrent() or false if the iterator has
     * resolved. If the iterator fails, the returned promise will fail with the same exception.
     *
     * @return Promise<bool>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation result
     */
    public function advance(): Promise;

    /**
     * Gets the last emitted value or throws an exception if the iterator has completed
     *
     * @return array<mixed, mixed>|null Value emitted from the iterator
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation result
     */
    public function getCurrent(): ?array;

    /**
     * Receive last insert id
     *
     * @param string $sequence
     *
     * @return string|int|null
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation result
     */
    public function lastInsertId(?string $sequence = null);

    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed
     *
     * @return int
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation result
     */
    public function affectedRows(): int;
}
