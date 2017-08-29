<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Backend;

use Desperado\ConcurrencyFramework\Infrastructure\Application\KernelInterface;

/**
 * Entry point backend
 */
interface BackendInterface
{
    /**
     * Run backend
     *
     * @param KernelInterface $kernel
     *
     * @return void
     */
    public function run(KernelInterface $kernel): void;

    /**
     * Stop backend
     *
     * @return void
     */
    public function stop(): void;
}
