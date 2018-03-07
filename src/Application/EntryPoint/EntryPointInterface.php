<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\EntryPoint;

/**
 * Entry point context
 */
interface EntryPointInterface
{
    /**
     * Run application
     *
     * @return void
     */
    public function run(): void;

    /**
     * Stop application
     *
     * @return void
     */
    public function stop(): void;
}
