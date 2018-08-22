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

namespace Desperado\ServiceBus\Tests\Stubs\Sagas;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\Annotations\SagaEventListener;
use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEventWithKey;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEventWithKey;

/**
 * @SagaHeader(
 *     idClass="Desperado\ServiceBus\Tests\Stubs\Sagas\TestSagaId",
 *     containingIdProperty="requestId",
 *     expireDateModifier="+1 year"
 * )
 */
final class CorrectSaga extends Saga
{
    /**
     * @inheritdoc
     */
    public function start(Command $command): void
    {

    }

    /**
     * @return void
     */
    public function doSomething(): void
    {
        $this->fire(new SecondEmptyCommand());
    }

    /**
     * @return void
     */
    public function doSomethingElse(): void
    {
        $this->raise(new SecondEventWithKey(uuid()));
    }

    /**
     * @return void
     */
    public function closeWithSuccessStatus(): void
    {
        $this->makeCompleted();
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @return void
     */
    private function onSomeFirstEvent(): void
    {
        $this->makeFailed('test reason');
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @SagaEventListener(
     *     containingIdProperty="key"
     * )
     *
     * @param FirstEventWithKey $event
     *
     * @return void
     */
    private function onFirstEventWithKey(FirstEventWithKey $event): void
    {
        $this->raise(new SecondEventWithKey($event->key()));
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @SagaEventListener(
     *     containingIdProperty="key"
     * )
     *
     * @param SecondEventWithKey $event
     *
     * @return void
     */
    private function onSecondEventWithKey(SecondEventWithKey $event): void
    {

    }
}
