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

use Desperado\Domain\Message\AbstractEvent;

/**
 *
 */
class TestConfigurationSagaEvent extends AbstractEvent
{
    /**
     * Identifier
     *
     * @var string
     */
    protected $operationId;

    /**
     *
     *
     * @return string
     */
    public function getOperationId(): string
    {
        return $this->operationId;
    }
}
