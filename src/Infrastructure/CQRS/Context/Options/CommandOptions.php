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

namespace Desperado\Framework\Infrastructure\CQRS\Context\Options;

/**
 * Command execution options
 */
class CommandOptions extends AbstractExecutionOptions implements MessageOptionsInterface
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
     * Log payload data
     *
     * @var bool
     */
    private $logPayload;

    /**
     * @param float       $retryDelay
     * @param int         $retryCount
     * @param bool        $logPayload
     * @param string|null $loggerChannel
     */
    public function __construct(
        float $retryDelay = 0.2,
        int $retryCount = 5,
        bool $logPayload = false,
        string $loggerChannel = null
    )
    {
        parent::__construct($loggerChannel);

        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
        $this->logPayload = $logPayload;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'retryDelay'    => $this->getRetryDelay(),
            'retryCount'    => $this->getRetryCount(),
            'loggerChannel' => $this->getLoggerChannel(),
            'logPayload'    => $this->getLogPayloadFlag()
        ];
    }

    /**
     * Get payload logging flag
     *
     * @return bool
     */
    public function getLogPayloadFlag(): bool
    {
        return $this->logPayload;
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
