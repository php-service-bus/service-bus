<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus;

use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\AggregateId;

final class CustomerId extends AggregateId
{

}


final class Customer extends Aggregate
{
    private $name;
    private $email;

    public static function register(
        CustomerId $id,
        string $name,
        string $email
    ): self
    {
        $self = new self();

        $self->raise(new CustomerRegisteredEvent($id, $name, $email));

        return $self;
    }

    public function rename(string $newName): self
    {
        $this->raise(
            new CustomerRenamed(
                $this->name, $newName
            )
        );
    }

    private function onCustomerRegisteredEvent(CustomerRegisteredEvent $event): void
    {
        $this->name  = $event->name;
        $this->email = $event->email;
    }

    private function onCustomerRenamed(CustomerRenamed $event): void
    {
        $this->name = $event->newName;
    }
}
