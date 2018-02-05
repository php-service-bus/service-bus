<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Application;

use Desperado\ServiceBus\Application\AbstractKernel;
use Desperado\ServiceBus\Tests\Services\Stabs\CorrectServiceWithHandlers;

/**
 *
 */
class TestKernel extends AbstractKernel
{
    /**
     * @inheritdoc
     */
    protected function init(): void
    {
        parent::init();

        $this->getMessageBusBuilder()->applyService(new CorrectServiceWithHandlers());
        $this->getMessageBusBuilder()->applyService(new TestMultipleTasksService());
    }
}
