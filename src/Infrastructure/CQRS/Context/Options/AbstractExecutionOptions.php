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
 * Base execution options class
 */
abstract class AbstractExecutionOptions
{
    /**
     * Logger channel
     *
     * @var string|null
     */
    private $loggerChannel;

    /**
     * @param null|string $loggerChannel
     */
    public function __construct(string $loggerChannel = null)
    {
        $this->loggerChannel = '' !== (string) $loggerChannel ? $loggerChannel : null;
    }

    /**
     * Get options as array
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Get logger channel
     *
     * @return null|string
     */
    public function getLoggerChannel(): ?string
    {
        return $this->loggerChannel;
    }
}
