<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Customer\Event;

use Desperado\Domain\Message\AbstractEvent;

/**
 *
 * @see RegisterCustomerCommand
 */
class FailedRegistrationEvent extends AbstractEvent
{
    /**
     * Operation ID
     *
     * @var string
     */
    protected $requestId;

    /**
     * Fail message
     *
     * @var string
     */
    protected $reason;

    /**
     * Get operation identifier
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
