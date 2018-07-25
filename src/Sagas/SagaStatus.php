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

namespace Desperado\ServiceBus\Sagas;

use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaStatus;

/**
 * SagaStatus of the saga
 */
final class SagaStatus
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_FAILED      = 'failed';
    public const STATUS_EXPIRED     = 'expired';

    private const LIST              = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED
    ];

    /**
     * SagaStatus ID
     *
     * @var string
     */
    private $value;

    /**
     * @param string $value
     *
     * @return self
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaStatus
     */
    public static function create(string $value): self
    {
        if(false === \in_array($value, self::LIST, true))
        {
            throw new InvalidSagaStatus($value);
        }

        $self        = new self();
        $self->value = $value;

        return $self;
    }

    /**
     * Create a new saga status
     *
     * @return self
     */
    public static function created(): self
    {
        $self        = new self();
        $self->value = self::STATUS_IN_PROGRESS;

        return $self;
    }

    /**
     * Creating the status of a successfully completed saga
     *
     * @return self
     */
    public static function completed(): self
    {
        $self        = new self();
        $self->value = self::STATUS_COMPLETED;

        return $self;
    }

    /**
     * Creating the status of an error-complete saga
     *
     * @return self
     */
    public static function failed(): self
    {
        $self        = new self();
        $self->value = self::STATUS_FAILED;

        return $self;
    }

    /**
     * Creation of the status of the expired life of the saga
     *
     * @return self
     */
    public static function expired(): self
    {
        $self        = new self();
        $self->value = self::STATUS_EXPIRED;

        return $self;
    }

    /**
     * Is processing status
     *
     * @return bool
     */
    public function inProgress(): bool
    {
        return self::STATUS_IN_PROGRESS === $this->value;
    }

    /**
     * @param SagaStatus $status
     *
     * @return bool
     */
    public function equals(SagaStatus $status): bool
    {
        return $this->value === $status->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
