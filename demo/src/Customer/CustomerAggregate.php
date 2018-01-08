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
use Desperado\ServiceBus\Demo\Customer\Event as CustomerEvents;
use Desperado\ServiceBus\Demo\Customer\Identity\CustomerAggregateIdentifier;

/**
 * Customer aggregate
 */
class CustomerAggregate extends AbstractAggregateRoot
{
    /**
     * Customer profile data
     *
     * @var Customer
     */
    private $profile;

    /**
     * Register customer
     *
     * @param CustomerCommands\RegisterCustomerCommand $command
     *
     * @return void
     */
    public function registerCustomer(CustomerCommands\RegisterCustomerCommand $command): void
    {
        /** @var CustomerCommands\RegisterCustomerCommand $command */

        $this->raiseEvent(
            CustomerEvents\CustomerRegisteredEvent::create([
                'requestId'    => $command->getRequestId(),
                'identifier'   => $this->getId()->toString(),
                'userName'     => $command->getUserName(),
                'displayName'  => $command->getDisplayName(),
                'email'        => $command->getEmail(),
                'passwordHash' => \password_hash($command->getPassword(), \PASSWORD_DEFAULT)
            ])
        );
    }

    /**
     * @param Event\CustomerRegisteredEvent $event
     *
     * @return void
     */
    final protected function onCustomerRegisteredEvent(CustomerEvents\CustomerRegisteredEvent $event): void
    {
        /** @var CustomerAggregateIdentifier $identifier */
        $identifier = $this->getId();

        $this->profile = Customer::create(
            $identifier,
            $event->getUserName(),
            $event->getDisplayName(),
            $event->getPasswordHash(),
            CustomerContacts::create(
                $event->getEmail()
            )
        );
    }
}
