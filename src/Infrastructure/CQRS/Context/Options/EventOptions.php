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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options;

/**
 * Event execution options
 */
class EventOptions extends AbstractExecutionOptions implements MessageOptionsInterface
{
    /**
     * Log payload data
     *
     * @var bool
     */
    private $logPayload;

    /**
     * @param bool        $logPayload
     * @param string|null $loggerChannel
     */
    public function __construct(
        bool $logPayload = false,
        string $loggerChannel = null
    )
    {
        parent::__construct($loggerChannel);

        $this->logPayload = $logPayload;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
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
}

