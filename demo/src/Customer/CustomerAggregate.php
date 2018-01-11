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

/**
 * Customer aggregate
 */
final class CustomerAggregate extends AbstractAggregateRoot
{
    /**
     * User name
     *
     * @var string
     */
    private $userName;

    /**
     * Display name
     *
     * @var string
     */
    private $displayName;

    /**
     * Hashed password
     *
     * @var string
     */
    private $passwordHash;

    /**
     * Email
     *
     * @var string
     */
    private $email;

    /**
     * Confirmed customer
     *
     * @see CustomerVerificationSaga
     *
     * @var bool
     */
    private $active = false;

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
     * Activate customer
     *
     * @param Command\ActivateCustomerCommand $command
     *
     * @return void
     */
    public function activate(CustomerCommands\ActivateCustomerCommand $command): void
    {
        $this->raiseEvent(
            CustomerEvents\CustomerActivatedEvent::create(['requestId' => $command->getRequestId()])
        );
    }

    /**
     * @param Event\CustomerRegisteredEvent $event
     *
     * @return void
     */
    protected function onCustomerRegisteredEvent(CustomerEvents\CustomerRegisteredEvent $event): void
    {
        $this->userName = $event->getUserName();
        $this->displayName = $event->getDisplayName();
        $this->passwordHash = $event->getPasswordHash();
        $this->email = $event->getEmail();
    }

    /**
     * Customer activated
     *
     * @param Event\CustomerActivatedEvent $event
     *
     * @return void
     */
    protected function onCustomerActivatedEvent(CustomerEvents\CustomerActivatedEvent $event): void
    {
        unset($event);

        $this->active = true;
    }
}
