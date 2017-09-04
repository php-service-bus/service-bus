<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\StorageManager;

use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;

/**
 * Storage manager interface
 */
interface StorageManagerInterface
{
    /**
     * Save changes to database; publish events (for Aggregates & Sagas); fire commands (for Sagas only)
     *
     * @param DeliveryContextInterface $context
     * @param callable|null            $onComplete
     * @param callable|null            $onFailed
     *
     * @return void
     */
    public function commit(DeliveryContextInterface $context, callable $onComplete = null, callable $onFailed = null): void;
}
