<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Serializer;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\AbstractSaga;

/**
 *
 */
class SerializerTestSaga extends AbstractSaga
{
    /**
     *
     *
     * @var string
     */
    private $testProperty;

    /**
     * @inheritdoc
     */
    public function start(AbstractCommand $command): void
    {
        /** @var SerializerTestCommand $command */

        $this->testProperty = $command->getTestProperty();
    }
}
