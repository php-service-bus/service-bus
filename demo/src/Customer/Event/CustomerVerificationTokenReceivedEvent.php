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
 * Verification from user received
 *
 * @see SendCustomerVerificationMessageCommand
 */
class CustomerVerificationTokenReceivedEvent extends AbstractEvent
{
    /**
     * Operation ID
     *
     * @var string
     */
    protected $requestId;

    /**
     * Customer identifier
     *
     * @var string
     */
    protected $identifier;

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
     * Get customer identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
