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

namespace Desperado\ServiceBus\Infrastructure\HttpClient\Data;

/**
 * Input file path
 *
 * @codeCoverageIgnore
 */
final class InputFilePath
{
    /**
     * Absolute file path
     *
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function fileName(): string
    {
        return \pathinfo($this->path, \PATHINFO_BASENAME);
    }
}
