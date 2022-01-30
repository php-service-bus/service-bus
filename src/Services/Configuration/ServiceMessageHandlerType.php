<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Services\Configuration;

enum ServiceMessageHandlerType: int
{
    case EVENT_LISTENER = 0;
    case COMMAND_HANDLER = 1;
}
