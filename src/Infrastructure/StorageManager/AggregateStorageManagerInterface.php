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

use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Aggregate\AbstractAggregateRoot;

/**
 * Aggregate storage manager interface
 */
interface AggregateStorageManagerInterface extends StorageManagerInterface
{
    /**
     * Get aggregate namespace
     *
     * @return string
     */
    public function getAggregateNamespace(): string;

    /**
     * Persist aggregate
     *
     * @param AbstractAggregateRoot $aggregateRoot
     *
     * @return void
     */
    public function persist(AbstractAggregateRoot $aggregateRoot): void;

    /**
     * Load aggregate
     *
     * @param IdentityInterface $identity
     * @param callable          $onLoaded function(AggregateRootInterface $aggregate = null) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function load(IdentityInterface $identity, callable $onLoaded, callable $onFailed = null): void;
}
