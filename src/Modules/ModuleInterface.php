<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Modules;

use Desperado\ServiceBus\MessageBus\MessageBusBuilder;

/**
 * Module interface
 *
 * @api
 */
interface ModuleInterface
{
    /**
     * Boot module
     *
     * @param MessageBusBuilder $messageBusBuilder
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\MessageBusAlreadyCreatedException
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void;
}
