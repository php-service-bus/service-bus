<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options;

/**
 * Command execution options
 */
class CommandOptions extends AbstractExecutionOptions
{
    /**
     * Retry delay
     *
     * @var float
     */
    private $retryDelay;

    /**
     * Retry count
     *
     * @var int
     */
    private $retryCount;

    /**
     * @param float       $retryDelay
     * @param int         $retryCount
     * @param null|string $loggerChannel
     */
    public function __construct(float $retryDelay = 0.2, int $retryCount = 5, string $loggerChannel = null)
    {
        parent::__construct($loggerChannel);

        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'retryDelay'    => $this->getRetryDelay(),
            'retryCount'    => $this->getRetryCount(),
            'loggerChannel' => $this->getLoggerChannel()
        ];
    }

    /**
     * Get retry delay
     *
     * @return float
     */
    public function getRetryDelay(): float
    {
        return $this->retryDelay;
    }

    /**
     * Get retry count
     *
     * @return int
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }
}
