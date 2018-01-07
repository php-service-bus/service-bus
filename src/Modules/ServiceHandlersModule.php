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
 * Initialize service
 */
class ServiceHandlersModule implements ModuleInterface
{
    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void
    {
        $self = new self();



        return $self;
    }

}
