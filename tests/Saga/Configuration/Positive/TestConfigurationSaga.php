<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Configuration\Positive;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\AbstractSaga;
use Desperado\ServiceBus\Annotations\Sagas;

/**
 * @Sagas\Saga(
 *     identifierNamespace="Desperado\ServiceBus\Tests\Saga\Configuration\Positive\TestConfigurationSagaIdentity",
 *     containingIdentifierProperty="operationId",
 *     expireDateModifier="+10 days"
 * )
 */
class TestConfigurationSaga extends AbstractSaga
{
    /**
     * @inheritdoc
     */
    public function start(AbstractCommand $command): void
    {

    }

    /**
     * @Sagas\SagaEventListener()
     *
     * @param TestConfigurationSagaEvent $event
     *
     * @return void
     */
    protected function onTestConfigurationSagaEvent(TestConfigurationSagaEvent $event): void
    {

    }

    /**
     * @Sagas\SagaEventListener(
     *     containingIdentifierProperty="customIdentifierField"
     * )
     *
     * @param TestConfigurationSecondSagaEvent $event
     *
     * @return void
     */
    protected function onTestConfigurationSecondSagaEvent(TestConfigurationSecondSagaEvent $event): void
    {

    }
}
