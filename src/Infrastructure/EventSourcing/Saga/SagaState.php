<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga;

use Desperado\ConcurrencyFramework\Domain\DateTime;
use Desperado\ConcurrencyFramework\Domain\EventSourced\SagaStateInterface;

/**
 * Saga state
 */
class SagaState implements SagaStateInterface
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
     * Created at datetime
     *
     * @var DateTime
     */
    private $createdAt;

    /***
     * Expire date
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
     * Expired at datetime
     *
     * @var DateTime|null
     */
    private $expiredAt;

    /**
     * Fail reason
     *
     * @var string
     */
    private $failReason;

    /**
     * Started status
     *
     * @param DateTime $createdAt
     * @param DateTime $expireDate
     *
     * @return SagaState
     */
    public static function create(DateTime $createdAt, DateTime $expireDate): self
    {
        return new self(
            self::STATUS_IN_PROGRESS,
            $createdAt,
            $expireDate
        );
    }

    /**
     * Mark as expired
     *
     * @param DateTime $expiredAt
     *
     * @return SagaState
     */
    public function expire(DateTime $expiredAt): self
    {
        $closedAt = clone $expiredAt;

        return new self(
            self::STATUS_EXPIRED,
            $this->createdAt,
            $this->expireDate,
            $closedAt,
            $expiredAt
        );
    }

    /**
     * Mark as failed
     *
     * @param string   $reason
     * @param DateTime $closedAt
     *
     * @return SagaState
     */
    public function fail(string $reason, DateTime $closedAt): self
    {
        return new self(
            self::STATUS_FAILED,
            $this->createdAt,
            $this->expireDate,
            $closedAt,
            null,
            $reason
        );
    }

    /**
     * Mark as complete
     *
     * @param DateTime $closedAt
     *
     * @return SagaState
     */
    public function complete(DateTime $closedAt): self
    {
        return new self(
            self::STATUS_COMPLETED,
            $this->createdAt,
            $this->expireDate,
            $closedAt
        );
    }

    /**
     * @inheritdoc
     */
    public function isClosed(): bool
    {
        return self::STATUS_IN_PROGRESS !== $this->status;
    }

    /**
     * @inheritdoc
     */
    public function isSuccess(): bool
    {
        return self::STATUS_COMPLETED === $this->status;
    }

    /**
     * @inheritdoc
     */
    public function isExpired(): bool
    {
        return self::STATUS_EXPIRED === $this->status;
    }

    /**
     * @inheritdoc
     */
    public function isFailed(): bool
    {
        return self::STATUS_EXPIRED === $this->status || self::STATUS_FAILED === $this->status;
    }

    /**
     * @inheritdoc
     */
    public function isProcessing(): bool
    {
        return self::STATUS_IN_PROGRESS === $this->status;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @inheritdoc
     */
    public function getExpireDate(): DateTime
    {
        return $this->expireDate;
    }

    /**
     * @inheritdoc
     */
    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    /**
     * @inheritdoc
     */
    public function getExpiredAt(): ?DateTime
    {
        return $this->expiredAt;
    }

    /**
     * @inheritdoc
     */
    public function getFailReason(): string
    {
        return (string) $this->failReason;
    }

    /**
     * @param int           $status
     * @param DateTime      $createdAt
     * @param DateTime      $expireDate
     * @param DateTime|null $closedAt
     * @param DateTime|null $expiredAt
     * @param string|null   $failReason
     */
    private function __construct(
        int $status,
        DateTime $createdAt,
        DateTime $expireDate,
        DateTime $closedAt = null,
        DateTime $expiredAt = null,
        string $failReason = null
    )
    {
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->expireDate = $expireDate;
        $this->closedAt = $closedAt;
        $this->expiredAt = $expiredAt;
        $this->failReason = $failReason;
    }
}
