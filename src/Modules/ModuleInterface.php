<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Modules;

use Desperado\CQRS\MessageBusBuilder;

/**
 * Module interface
 */
interface ModuleInterface
{
    /**
     * Boot module
     *
     * @param MessageBusBuilder $messageBusBuilder
     *
     * @return void
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void;
}
