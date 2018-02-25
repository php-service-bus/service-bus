<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga;

use Desperado\Domain\DateTime;

/**
 * Saga state
 */
final class SagaState
{
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_FAILED = 3;
    public const STATUS_EXPIRED = 4;

    /**
     * Status ID
     *
     * @var int
     */
    private $status;

    /**
     * Date of creation of the saga
     *
     * @var DateTime
     */
    private $createdAt;

    /**
     * The expiration date of the saga
     *
     * @var DateTime
     */
    private $expireDate;

    /**
     * Closed at datetime
     *
     * @var DateTime|null
     */
    private $closedAt;

    /**
     * Closing date of the saga
     *
     * @var DateTime|null
     */
    private $expiredAt;

    /**
     * Status reason
     *
     * @var string|null
     */
    private $reason;

    /**
     * Started status
     *
     * @param DateTime $createdAt
     * @param DateTime $expireDate
     *
     * @return self
     */
    public static function create(DateTime $createdAt, DateTime $expireDate): SagaState
    {
        $self = new self();
        $self->status = self::STATUS_IN_PROGRESS;
        $self->createdAt = $createdAt;
        $self->expireDate = $expireDate;

        return $self;
    }

    /**
     * Mark as expired
     *
     * @param DateTime $expiredAt
     *
     * @return self
     */
    public function expire(DateTime $expiredAt): SagaState
    {
        $closedAt = clone $expiredAt;

        $self = new self();
        $self->status = self::STATUS_EXPIRED;
        $self->createdAt = $this->createdAt;
        $self->expireDate = $this->expireDate;
        $self->closedAt = $closedAt;
        $self->expiredAt = $expiredAt;

        return $self;
    }

    /**
     * Mark as failed
     *
     * @param string   $reason
     * @param DateTime $closedAt
     *
     * @return self
     */
    public function fail(string $reason, DateTime $closedAt): SagaState
    {
        $self = new self();
        $self->status = self::STATUS_FAILED;
        $self->createdAt = $this->createdAt;
        $self->expireDate = $this->expireDate;
        $self->closedAt = $closedAt;
        $self->reason = $reason;

        return $self;
    }

    /**
     * Mark as complete
     *
     * @param DateTime $closedAt
     * @param string   $reason
     *
     * @return self
     */
    public function complete(DateTime $closedAt, string $reason): self
    {
        $self = new self();
        $self->status = self::STATUS_COMPLETED;
        $self->createdAt = $this->createdAt;
        $self->expireDate = $this->expireDate;
        $self->closedAt = $closedAt;
        $self->reason = $reason;

        return $self;
    }

    /**
     * Is closed status?
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return self::STATUS_IN_PROGRESS !== $this->status;
    }

    /**
     * Is success status?
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return self::STATUS_COMPLETED === $this->status;
    }

    /**
     * Is expired status?
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return self::STATUS_EXPIRED === $this->status;
    }

    /**
     * Is failed status?
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return self::STATUS_EXPIRED === $this->status || self::STATUS_FAILED === $this->status;
    }

    /**
     * Is processing status?
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return self::STATUS_IN_PROGRESS === $this->status;
    }

    /**
     * Get the current status of the saga
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status;
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
     * Get the expiration date of the saga
     *
     * @return DateTime
     */
    public function getExpireDate(): DateTime
    {
        return $this->expireDate;
    }

    /**
     * Get the closing date of the saga
     *
     * @return DateTime|null
     */
    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    /**
     * Get the date when the saga was closed after the expiration of life
     *
     * @return DateTime|null
     */
    public function getExpiredAt(): ?DateTime
    {
        return $this->expiredAt;
    }

    /**
     * Get the reason for the status of the saga
     *
     * @return string
     */
    public function getStatusReason(): string
    {
        return (string) $this->reason;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
