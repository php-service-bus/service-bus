<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Alerting;

use Amp\Promise;

interface AlertingProvider
{
    public function send(AlertMessage $message, ?AlertContext $context = null): Promise;
}
