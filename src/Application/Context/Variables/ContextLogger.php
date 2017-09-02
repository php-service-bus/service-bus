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

namespace Desperado\ConcurrencyFramework\Application\Context\Variables;

use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Logger\LoggerRegistry;
use Psr\Log\LoggerInterface;

/**
 * Context logger
 */
class ContextLogger
{
    /**
     * Context logger
     *
     * @param string|null $channelName
     *
     * @return LoggerInterface
     */
    public function getLogger(string $channelName = null): LoggerInterface
    {
        return LoggerRegistry::getLogger($channelName);
    }
}
