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
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\Annotations\SagaEventListener;
use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;

/**
 * @SagaHeader(
 *     idClass="Desperado\ServiceBus\Tests\Stubs\Sagas\TestSagaId",
 *     containingIdProperty="requestId",
 *     expireDateModifier="+1 year"
 * )
 */
final class SagaWithMultipleListenerArgs extends Saga
{
    /**
     * @inheritdoc
     */
    public function start(Command $command): void
    {

    }

    /**
     * @noinspection PhpUndefinedClassInspection
     *
     * @SagaEventListener()
     *
     * @param FirstEmptyEvent    $firstEmptyEvent
     * @param SecondEmptyCommand $command
     *
     * @return void
     */
    public function onSomeEvent(FirstEmptyEvent $firstEmptyEvent, SecondEmptyCommand $command): void
    {

    }
}
