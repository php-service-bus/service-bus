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
 *
 * @see RegisterCustomerCommand
 */
class CustomerRegisteredEvent extends AbstractEvent
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
     * Customer username
     *
     * @var string
     */
    protected $userName;

    /**
     * Customer display name
     *
     * @var string
     */
    protected $displayName;

    /**
     * Customer email
     *
     * @var string
     */
    protected $email;

    /**
     * Customer password hash
     *
     * @var string
     */
    protected $passwordHash;

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
     * Get username
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Get email
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

    /**
     * Get customer hashed password
     *
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
}
