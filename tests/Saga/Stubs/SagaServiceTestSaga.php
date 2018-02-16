<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Stubs;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\AbstractSaga;
use Desperado\ServiceBus\Annotations\Sagas;

/**
 * @Sagas\Saga(
 *     identifierNamespace="Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestIdentifier",
 *     containingIdentifierProperty="requestId",
 *     expireDateModifier="+15 days"
 * )
 */
class SagaServiceTestSaga extends AbstractSaga
{
    /**
     * @inheritdoc
     */
    public function start(AbstractCommand $command): void
    {
        $this->fire(SagaServiceTestCommand::create());
    }

    /**
     * @Sagas\SagaEventListener()
     *
     * @param SagaServiceTestEvent $event
     *
     * @return void
     */
    protected function onSagaServiceTestEvent(SagaServiceTestEvent $event): void
    {

    }
}
