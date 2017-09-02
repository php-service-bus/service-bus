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

namespace Desperado\Framework\Domain\Repository;

use Desperado\Framework\Domain\EventSourced\AggregateRootInterface;
use Desperado\Framework\Domain\Identity\IdentityInterface;

/**
 * Aggregate repository
 */
interface AggregateRepositoryInterface
{
    /**
     * Load aggregate
     *
     * @param IdentityInterface $identity
     * @param string            $aggregateNamespace
     *
     * @return AggregateRootInterface|null
     */
    public function load(IdentityInterface $identity, string $aggregateNamespace): ?AggregateRootInterface;

    /**
     * Save aggregate
     *
     * @param AggregateRootInterface $aggregateRoot
     *
     * @return void
     */
    public function save(AggregateRootInterface $aggregateRoot): void;
}
