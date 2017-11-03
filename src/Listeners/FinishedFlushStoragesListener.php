<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Listeners;

use Desperado\Framework\Application\ApplicationLogger;
use Desperado\Framework\Events\OnFlushExecutionFinishedEvent;

/**
 * Log end flush storages
 */
class FinishedFlushStoragesListener
{
    /**
     * Log data
     *
     * @param OnFlushExecutionFinishedEvent $event
     *
     * @return void
     */
    public function __invoke(OnFlushExecutionFinishedEvent $event): void
    {
        ApplicationLogger::debug(
            'core',
            \sprintf(
                'Saving data/sending messages after processing the message "%s" completed (%s sec.)',
                \get_class($event->getMessage()),
                \round($event->getExecutionTime(), 4)
            )
        );
    }
}
