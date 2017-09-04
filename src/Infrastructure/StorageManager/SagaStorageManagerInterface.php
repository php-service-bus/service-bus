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
use Desperado\Framework\Infrastructure\EventSourcing\Saga\AbstractSaga;

/**
 * Saga storage manager
 */
interface SagaStorageManagerInterface extends StorageManagerInterface
{
    /**
     * Get saga namespace
     *
     * @return string
     */
    public function getSagaNamespace(): string;

    /**
     * Persist saga
     *
     * @param AbstractSaga $saga
     *
     * @return void
     */
    public function persist(AbstractSaga $saga): void;

    /**
     * Load saga
     *
     * @param IdentityInterface $identity
     * @param callable          $onLoaded function(AbstractSaga $aggregate) {}
     * @param callable|null     $onFailed function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function load(IdentityInterface $identity, callable $onLoaded, callable $onFailed = null): void;
}
