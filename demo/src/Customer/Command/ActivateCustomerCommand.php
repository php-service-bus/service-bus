<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Customer\Command;

use Desperado\Domain\Message\AbstractCommand;

/**
 *
 * @see CustomerActivatedEvent
 */
class ActivateCustomerCommand extends AbstractCommand
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
