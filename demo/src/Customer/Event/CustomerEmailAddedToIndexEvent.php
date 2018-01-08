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
 * The relationship between the email and the customer identifier stored
 */
class CustomerEmailAddedToIndexEvent extends AbstractEvent
{
    /**
     * Customer email
     *
     * @var string
     */
    protected $email;

    /**
     * Customer identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Get customer email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
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
