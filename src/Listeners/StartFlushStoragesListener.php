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
use Desperado\Framework\Events\OnFlushExecutionStartedEvent;

/**
 * Log start flush storages event
 */
class StartFlushStoragesListener
{
    /**
     * Log data
     *
     * @param OnFlushExecutionStartedEvent $event
     *
     * @return void
     */
    public function __invoke(OnFlushExecutionStartedEvent $event): void
    {
        

        ApplicationLogger::debug(
            'core',
            \sprintf(
                'Execute saving data/sending messages after processing the message "%s" completed',
                \get_class($event->getMessage())
            )
        );
    }
}
