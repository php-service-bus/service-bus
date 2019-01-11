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

namespace Desperado\ServiceBus\EventSourcingProjections;

/**
 *
 */
interface ReadModel
{
    /**
     * Receive read model data
     *
     * @return array<string, string|int|float|null>
     */
    public function toArray(): array;
}
