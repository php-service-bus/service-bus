<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Customer;

use Desperado\EventSourcing\AbstractAggregateRoot;
use Desperado\ServiceBus\Demo\Customer\Command as CustomerCommands;

/**
 * Customer aggregate
 */
class CustomerAggregate extends AbstractAggregateRoot
{
    /**
     * Register customer
     *
     * @param CustomerCommands\RegisterCustomerCommand $command
     *
     * @return void
     */
    public function registerCustomer(CustomerCommands\RegisterCustomerCommand $command): void
    {

    }
}
