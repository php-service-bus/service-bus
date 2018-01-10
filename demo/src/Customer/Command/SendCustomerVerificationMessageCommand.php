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
 * Send a message to the user confirming the profile
 *
 * @see CustomerVerificationTokenReceivedEvent
 * @see CustomerAggregateNotFoundEvent
 */
class SendCustomerVerificationMessageCommand extends AbstractCommand
{
    /**
     * Operation identifier
     *
     * @Assert\NotBlank(
     *     message="operation identifier must be specified"
     * )
     *
     * @var string
     */
    protected $requestId;

    /**
     * Customer aggregate identifier
     *
     * @var string
     */
    protected $customerIdentifier;

    /**
     * Get operation id
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
    public function getCustomerIdentifier(): string
    {
        return $this->customerIdentifier;
    }
}
