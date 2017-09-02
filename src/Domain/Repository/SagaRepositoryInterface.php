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

namespace Desperado\ConcurrencyFramework\Domain\Repository;

use Desperado\ConcurrencyFramework\Domain\EventSourced\SagaInterface;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;

/**
 * Saga repository
 */
interface SagaRepositoryInterface
{
    /**
     * Load saga
     *
     * @param IdentityInterface $identity
     * @param string            $sagaNamespace
     *
     * @return SagaInterface|null
     */
    public function load(IdentityInterface $identity, string $sagaNamespace): ?SagaInterface;

    /**
     * Save saga
     *
     * @param SagaInterface $saga
     *
     * @return void
     */
    public function save(SagaInterface $saga): void;

    /**
     * Delete saga from storage
     *
     * @param IdentityInterface $identity
     *
     * @return void
     */
    public function remove(IdentityInterface $identity): void;
}
