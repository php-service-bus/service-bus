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

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Storage\Exceptions\OneResultExpected;

/**
 * Collect iterator data
 * Not recommended for use on large amounts of data
 *
 * @param ResultSet $iterator
 *
 * @return Promise<array|null>
 *
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation result
 */
function fetchAll(ResultSet $iterator): Promise
{
    /** @psalm-suppress InvalidArgument */
    return call(
    /** @psalm-suppress InvalidReturnType */
        static function(ResultSet $iterator): \Generator
        {
            $array = [];

            while(yield $iterator->advance())
            {
                $array[] = $iterator->getCurrent();
            }

            return $array;
        },
        $iterator
    );
}

/**
 * Extract 1 result
 *
 * @param ResultSet $iterator
 *
 * @return Promise<array|null>
 *
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ResultSetIterationFailed Error getting operation result
 * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\OneResultExpected The result must contain only 1 row
 */
function fetchOne(ResultSet $iterator): Promise
{
    /** @psalm-suppress InvalidArgument */
    return call(
        static function(ResultSet $iterator): \Generator
        {
            $collection   = yield fetchAll($iterator);
            $resultsCount = \count($collection);

            if(0 === $resultsCount || 1 === $resultsCount)
            {
                $endElement = \end($collection);

                unset($collection);

                return false !== $endElement ? $endElement : null;
            }

            throw new OneResultExpected(
                \sprintf(
                    'A single record was requested, but the result of the query execution contains several ("%d")',
                    $resultsCount
                )
            );
        },
        $iterator
    );
}
