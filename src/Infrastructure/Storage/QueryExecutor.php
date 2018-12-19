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
 * Query execution interface
 */
interface QueryExecutor
{
    /**
     * Execute query
     *
     * @param string $queryString
     * @param array  $parameters
     *
     * @return Promise<\Desperado\ServiceBus\Infrastructure\Storage\ResultSet>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed Duplicate entry
     */
    public function execute(string $queryString, array $parameters = []): Promise;
}
