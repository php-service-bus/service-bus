<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Processor\Positive;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\AbstractSaga;

/**
 *
 */
class TestEventProcessorSaga extends AbstractSaga
{
    /**
     * @inheritdoc
     */
    public function start(AbstractCommand $command): void
    {

    }

    protected function onTestEventProcessorSagaEvent(TestEventProcessorSagaEvent $event): void
    {
        unset($event);

        $this->fire(TestEventProcessorCommand::create());
    }
}