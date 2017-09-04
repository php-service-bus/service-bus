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

use Desperado\Framework\Domain\EventSourced\SagaInterface;
use Desperado\Framework\Domain\Identity\IdentityInterface;

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
     * @param callable          $onLoaded function(SagaInterface $saga = null) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function load(
        IdentityInterface $identity,
        string $sagaNamespace,
        callable $onLoaded,
        callable $onFailed = null
    ): void;

    /**
     * Save saga
     *
     * @param SagaInterface $saga
     * @param callable|null $onSaved  function() {}
     * @param callable|null $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function save(SagaInterface $saga, callable $onSaved = null, callable $onFailed = null): void;

    /**
     * Delete saga from storage
     *
     * @param IdentityInterface $identity
     * @param callable          $onRemoved function() {}
     * @param callable|null     $onFailed  function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function remove(IdentityInterface $identity, callable $onRemoved = null, callable $onFailed = null): void;
}
