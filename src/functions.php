<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus;

/**
 * Formats bytes into a human readable string
 *
 * @param int $bytes
 *
 * @return string
 */
function formatBytes(int $bytes): string
{
    if(1024 * 1024 < $bytes)
    {
        return \round($bytes / 1024 / 1024, 2) . ' mb';
    }

    if(1024 < $bytes)
    {
        return \round($bytes / 1024, 2) . ' kb';
    }

    return $bytes . ' b';
}
