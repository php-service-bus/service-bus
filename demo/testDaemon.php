<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

include_once __DIR__ . '/../vendor/autoload.php';

use Desperado\ServiceBus\Demo\Application\Bootstrap;
use Desperado\Domain\ThrowableFormatter;

try
{
    $entryPoint = Bootstrap::boot(
        __DIR__ . '/..',
        __DIR__ . '/cache',
        __DIR__ . '/.env'
    );

    $entryPoint->run(['demo']);
}
catch(\Throwable $throwable)
{
    echo ThrowableFormatter::toString($throwable) . \PHP_EOL;
}
