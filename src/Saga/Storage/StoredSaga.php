<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Storage;

use Desperado\Domain\DateTime;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;

/**
 * Stored saga DTO
 */
final class StoredSaga
{
    /**
     * Saga identifier
     *
     * @var string
     */
    private $identifier;

    /**
     * Saga identifier class namespace
     *
     * @var string
     */
    private $identifierNamespace;

    /**
     * Saga namespace
     *
     * @var string
     */
    private $sagaNamespace;

    /**
     * Serialized representation of the saga
     *
     * @var string
     */
    private $payload;

    /**
     * Current state of the saga
     *
     * @var int
     */
    private $state;

    /**
     * Date of creation of the saga
     *
     * @var DateTime
     */
    private $createdAt;

    /**
     * Closing date of the saga
     *
     * @var DateTime|null
     */
    private $closedAt;

    /**
     * @param AbstractSagaIdentifier $id
     * @param string                 $payload
     * @param int                    $state
     * @param DateTime               $createdAt
     * @param DateTime|null          $closedAt
     *
     * @return self
     */
    public static function create(
        AbstractSagaIdentifier $id,
        string $payload,
        int $state,
        DateTime $createdAt,
        ?DateTime $closedAt = null
    ): self
    {
        $self = new self();
        $self->identifier = $id->toString();
        $self->identifierNamespace = $id->getIdentityClass();
        $self->sagaNamespace = $id->getSagaNamespace();
        $self->payload = $payload;
        $self->state = $state;
        $self->createdAt = $createdAt;
        $self->closedAt = $closedAt;

        return $self;
    }

    /**
     * @param string      $sagaNamespace
     * @param string      $identifier
     * @param string      $identifierNamespace
     * @param string      $payload
     * @param int         $state
     * @param string      $createdAt
     * @param null|string $closedAt
     *
     * @return StoredSaga
     */
    public static function restore(
        string $sagaNamespace,
        string $identifier,
        string $identifierNamespace,
        string $payload,
        int $state,
        string $createdAt,
        ?string $closedAt = null
    ): self
    {
        $identifier = new $identifierNamespace($identifier, $sagaNamespace);

        return self::create(
            $identifier,
            $payload,
            $state,
            DateTime::fromString($createdAt),
            null !== $closedAt
                ? DateTime::fromString($closedAt)
                : null
        );
    }

    /**
     * Get saga identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the namespace of the saga identifier class
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
     * Get serialized representation of the saga
     *
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * Get the current status of the saga
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Get the date of creation of the saga
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Is closed saga?
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return null !== $this->closedAt;
    }

    /**
     * Get the closing date of the saga
     *
     * @return DateTime|null
     */
    public function getClosedAt()
    {
        return $this->closedAt;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
