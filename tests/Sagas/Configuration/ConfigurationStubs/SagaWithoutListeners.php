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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration\ConfigurationStubs;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use Desperado\ServiceBus\Sagas\Saga;

/**
 * @SagaHeader(
 *     idClass="Desperado\ServiceBus\Tests\Sagas\Configuration\ConfigurationStubs\TestSagaId",
 *     containingIdProperty="qwerty"
 * )
 */
final class SagaWithoutListeners extends Saga
{
    /**
     * @inheritdoc
     */
    public function start(Command $command): void
    {

    }
}