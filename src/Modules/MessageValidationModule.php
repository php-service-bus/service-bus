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

use Desperado\CQRS\Behaviors\ValidationBehavior;
use Desperado\CQRS\MessageBus\MessageBusBuilder;

/**
 * Provide message validation
 */
class MessageValidationModule implements ModuleInterface
{
    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void
    {
        $messageBusBuilder->pushBehavior(ValidationBehavior::create());
    }
}
