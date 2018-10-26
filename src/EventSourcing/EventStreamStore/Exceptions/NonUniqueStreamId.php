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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions;

/**
 * Attempt to add a stream with an existing identifier
 */
final class NonUniqueStreamId extends \RuntimeException
{
    /**
     * @param string $id
     * @param string $class
     */
    public function __construct(string $id, string $class)
    {
        parent::__construct(
            \sprintf('attempt to add a stream with an existing identifier "%s:%s"', $id, $class)
        );
    }
}
