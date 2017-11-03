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
use Desperado\Framework\Events\OnMessageExecutionFinishedEvent;

/**
 * Log execution message completed
 */
class LogFinishedMessageExecutionListener
{
    /**
     * Log finished message
     *
     * @param OnMessageExecutionFinishedEvent $event
     *
     * @return void
     */
    public function __invoke(OnMessageExecutionFinishedEvent $event): void
    {
        ApplicationLogger::debug(
            'core',
            \sprintf(
                'Processing of message "%s" completed',
                \get_class($event->getMessage()),
                \round($event->getExecutionTime(), 4)
            )
        );
    }
}
