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
 * Saga launch command
 */
class StartVerificationSagaCommand extends AbstractCommand
{
    /**
     * Customer aggregate identifier
     *
     * @var string
     */
    protected $customerIdentifier;

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
