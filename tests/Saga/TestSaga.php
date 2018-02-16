<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\AbstractSaga;

/**
 *
 */
class TestSaga extends AbstractSaga
{
    /**
     * @inheritdoc
     */
    public function start(AbstractCommand $command): void
    {
        $this->fire($command);
    }

    /**
     * @param string $reason
     *
     * @return void
     */
    public function closeCommand(string $reason): void
    {
        $this->fail($reason);
    }

    /**
     * @return void
     */
    public function expireCommand(): void
    {
        $this->expire();
    }

    /**
     * @param string $reason
     *
     * @return void
     */
    public function completeCommand(string $reason): void
    {
        $this->complete($reason);
    }
}
