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

namespace Desperado\ServiceBus\Tests\Sagas\Mocks;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Sagas\Saga;

/**
 *
 */
final class SimpleSaga extends Saga
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
        $this->fire(new SomeCommand());
    }

    /**
     * @return void
     */
    public function doSomethingElse(): void
    {
        $this->raise(new SomeFirstEvent());
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
}
