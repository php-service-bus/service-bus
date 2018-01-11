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

use Desperado\Domain\Identity\IdentityInterface;
use Desperado\EventSourcing\AbstractAggregateRoot;
use Desperado\EventSourcing\IndexInterface;
use Desperado\ServiceBus\Demo\Customer\Event\CustomerEmailAddedToIndexEvent;
use Desperado\ServiceBus\Demo\Customer\Identity\CustomerAggregateIdentifier;
use Desperado\ServiceBus\Demo\Customer\Identity\CustomerEmailIndexIdentifier;

/**
 * Index stores the relationship email address of the user and his identity
 */
final class CustomerEmailIndex extends AbstractAggregateRoot implements IndexInterface
{
    /**
     * Relationship email address of the user and his identity
     *
     * @var array
     */
    private $collection = [];

    /**
     * @inheritdoc
     *
     * @return IdentityInterface
     */
    public static function getIndexIdentifier(): IdentityInterface
    {
        return new CustomerEmailIndexIdentifier('1dea6550-a1ae-4cc2-a403-89b51816ed61');
    }

    /**
     * Save link between email and customer ID
     *
     * @param string                      $email
     * @param CustomerAggregateIdentifier $customerAggregateIdentifier
     */
    public function store(string $email, CustomerAggregateIdentifier $customerAggregateIdentifier): void
    {
        $this->raiseEvent(
            CustomerEmailAddedToIndexEvent::create([
                'email'      => $email,
                'identifier' => $customerAggregateIdentifier->toString()
            ])
        );
    }

    /**
     * Is the identifier for this email added to the index?
     *
     * @param string $email
     *
     * @return bool
     */
    public function hasIdentifier(string $email): bool
    {
        return isset($this->collection[$email]);
    }

    /**
     * Get user ID by its email
     *
     * @param string $email
     *
     * @return CustomerAggregateIdentifier|null
     */
    public function getIdentifier(string $email): ?CustomerAggregateIdentifier
    {
        return true === $this->hasIdentifier($email)
            ? new CustomerAggregateIdentifier($this->collection[$email])
            : null;
    }

    /**
     * @param CustomerEmailAddedToIndexEvent $event
     *
     * @return void
     */
    protected function onCustomerEmailAddedToIndexEvent(CustomerEmailAddedToIndexEvent $event): void
    {
        $this->collection[$event->getEmail()] = $event->getIdentifier();
    }
}
