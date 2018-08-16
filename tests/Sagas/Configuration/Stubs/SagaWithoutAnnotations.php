<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Sagas\Saga;

/**
 *
 */
final class SagaWithoutAnnotations extends Saga
{
    /**
     * @inheritdoc
     */
    public function start(Command $command): void
    {

    }
}
