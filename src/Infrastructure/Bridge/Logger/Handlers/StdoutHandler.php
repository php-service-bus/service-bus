<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Bridge\Logger\Handlers;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Simple console echo handler
 */
class StdoutHandler extends AbstractProcessingHandler
{
    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        if('cli' === \PHP_SAPI)
        {
            echo (string) $record['formatted'];
        }
    }
}
