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

namespace Desperado\Framework\Domain\Application;

/**
 * Entry point backend
 */
interface BackendInterface
{
    /**
     * Run backend
     *
     * @param KernelInterface $kernel
     * @param array           $clients
     *
     * @return void
     */
    public function run(KernelInterface $kernel, array $clients): void;

    /**
     * Stop backend
     *
     * @return void
     */
    public function stop(): void;
}
