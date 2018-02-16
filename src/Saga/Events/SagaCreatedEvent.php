<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas\Events;

use Desperado\Domain\Message\AbstractEvent;

/**
 * A new saga was created
 */
class SagaCreatedEvent extends AbstractEvent
{
    /**
     * Saga identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Saga identifier class namespace
     *
     * @var string
     */
    protected $identifierNamespace;

    /**
     * Saga class namespace
     *
     * @var string
     */
    protected $sagaNamespace;

    /**
     * Date of creation of the saga
     *
     * @var string
     */
    protected $createdAt;

    /**
     * The expiration date of the saga
     *
     * @var string
     */
    protected $expireDate;

    /**
     * Get saga identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get saga identifier class namespace
     *
     * @return string
     */
    public function getIdentifierNamespace(): string
    {
        return $this->identifierNamespace;
    }

    /**
     * Get saga class namespace
     *
     * @return string
     */
    public function getSagaNamespace(): string
    {
        return $this->sagaNamespace;
    }

    /**
     * Get the date of creation of the saga
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Get the expiration date of the saga
     *
     * @return string
     */
    public function getExpireDate(): string
    {
        return $this->expireDate;
    }
}
