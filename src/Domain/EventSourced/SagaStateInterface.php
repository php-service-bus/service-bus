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

namespace Desperado\ConcurrencyFramework\Domain\EventSourced;

use Desperado\ConcurrencyFramework\Domain\DateTime;


/**
 * Saga state
 */
interface SagaStateInterface
{
    /**
     * Is processing status
     *
     * @return bool
     */
    public function isProcessing(): bool;

    /**
     * Is closed status
     *
     * @return bool
     */
    public function isClosed(): bool;

    /**
     * Is success status
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Is expired status
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Is failed status
     *
     * @return bool
     */
    public function isFailed(): bool;

    /**
     * Get created at datetime
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime;

    /**
     * Get expire date
     *
     * @return DateTime
     */
    public function getExpireDate(): DateTime;

    /**
     * Get closed at datetime
     *
     * @return DateTime|null
     */
    public function getClosedAt(): ?DateTime;

    /**
     * Get expired at datetime
     *
     * @return DateTime|null
     */
    public function getExpiredAt(): ?DateTime;

    /**
     * Failed reason
     *
     * @return string
     */
    public function getFailReason(): string;

    /**
     * Get saga status
     *
     * @return int
     */
    public function getStatus(): int;
}
