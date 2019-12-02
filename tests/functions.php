<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests;

/**
 * @param string $path
 */
function removeDirectory(string $path): void
{
    $files = \glob(\preg_replace('/(\*|\?|\[)/', '[$1]', $path) . '/{,.}*', GLOB_BRACE);
    foreach ($files as $file)
    {
        if ($file === $path . '/.' || $file === $path . '/..')
        {
            continue;
        }
        \is_dir($file) ? removeDirectory($file) : \unlink($file);
    }
    \rmdir($path);
}
