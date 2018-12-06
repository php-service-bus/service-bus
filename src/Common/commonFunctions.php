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

namespace Desperado\ServiceBus\Common;

use Ramsey\Uuid\Uuid;

/**
 * @noinspection PhpDocMissingThrowsInspection
 *
 * Generate a version 4 (random) UUID.
 *
 * @return string
 */
function uuid(): string
{
    /** @noinspection PhpUnhandledExceptionInspection */
    return Uuid::uuid4()->toString();
}

/**
 * @param string $path
 *
 * @return void
 */
function removeDirectory(string $path): void
{
    $files = \glob(\preg_replace('/(\*|\?|\[)/', '[$1]', $path) . '/{,.}*', GLOB_BRACE);

    foreach($files as $file)
    {
        if($file === $path . '/.' || $file === $path . '/..')
        {
            continue;
        }

        \is_dir($file) ? removeDirectory($file) : \unlink($file);
    }

    \rmdir($path);
}
