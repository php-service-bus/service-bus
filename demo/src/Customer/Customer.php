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

use Desperado\ServiceBus\Demo\Customer\Identity\CustomerAggregateIdentifier;

/**
 * Customer DTO
 */
class Customer
{
    /**
     * Identifier
     *
     * @var CustomerAggregateIdentifier
     */
    private $id;

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
     * Contact data
     *
     * @var CustomerContacts
     */
    private $contacts;

    /**
     * @param CustomerAggregateIdentifier $id
     * @param string                      $userName
     * @param string                      $displayName
     * @param string                      $passwordHash
     * @param CustomerContacts            $contacts
     *
     * @return Customer
     */
    public static function create(
        CustomerAggregateIdentifier $id,
        string $userName,
        string $displayName,
        string $passwordHash,
        CustomerContacts $contacts
    ): self
    {
        $self = new self();

        $self->id = $id;
        $self->userName = $userName;
        $self->displayName = $displayName;
        $self->passwordHash = $passwordHash;
        $self->contacts = $contacts;

        return $self;
    }

    /**
     * Change customer password
     *
     * @param string $newPasswordHash
     *
     * @return Customer
     */
    public function changePassword(string $newPasswordHash): self
    {
        return self::create(
            $this->id,
            $this->userName,
            $this->displayName,
            $newPasswordHash,
            $this->contacts
        );
    }

    /**
     * Check password is correct
     *
     * @param string $password
     *
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return \password_verify($password, $this->passwordHash);
    }

    /**
     * Get identifier
     *
     * @return CustomerAggregateIdentifier
     */
    public function getId(): CustomerAggregateIdentifier
    {
        return $this->id;
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
     * Get contacts
     *
     * @return CustomerContacts
     */
    public function getContacts(): CustomerContacts
    {
        return $this->contacts;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
