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

/**
 * Customer contacts DTO
 */
class CustomerContacts
{
    /**
     * Email
     *
     * @var string
     */
    private $email;

    /**
     * @param string $email
     *
     * @return CustomerContacts
     */
    public static function create(string $email): self
    {
        $self = new self();
        $self->email = $email;

        return $self;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
