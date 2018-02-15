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

use Desperado\Domain\Message\AbstractEvent;

/**
 *
 */
class SagaServiceTestEvent extends AbstractEvent
{
    /**
     * @var string
     */
    protected $requestId;

    /**
     *
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
