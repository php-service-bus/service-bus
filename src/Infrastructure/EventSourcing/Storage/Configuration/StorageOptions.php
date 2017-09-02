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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\Configuration;

/**
 * Storage options
 */
class StorageOptions
{
    /**
     * Charset
     *
     * @var string
     */
    private $encoding;

    /**
     * @param string $encoding
     */
    public function __construct(string $encoding = 'UTF-8')
    {
        $this->encoding = $encoding;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }
}
