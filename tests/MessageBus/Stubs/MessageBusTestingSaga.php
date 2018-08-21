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

namespace Desperado\ServiceBus\Tests\MessageBus\Stubs;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Sagas\Annotations\SagaEventListener;
use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use Desperado\ServiceBus\Sagas\Saga;

/**
 * @SagaHeader(
 *     idClass="Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingSagaId",
 *     containingIdProperty="requestId",
 *     expireDateModifier="+1 day"
 * )
 */
final class MessageBusTestingSaga extends Saga
{
    /**
     * @inheritdoc
     */
    public function start(Command $command): void
    {

    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @SagaEventListener()
     *
     * @param MessageBusTestingEvent $event
     *
     * @return void
     */
    private function onMessageBusTestingEvent(MessageBusTestingEvent $event): void
    {

    }
}
