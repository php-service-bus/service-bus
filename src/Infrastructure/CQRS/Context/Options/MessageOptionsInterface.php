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
 * Message options
 */
interface MessageOptionsInterface
{
    /**
     * Get payload logging flag
     *
     * @return bool
     */
    public function getLogPayloadFlag(): bool;

    /**
     * Get logger channel
     *
     * @return null|string
     */
    public function getLoggerChannel(): ?string;
}
