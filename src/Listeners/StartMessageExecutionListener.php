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
use Desperado\Framework\Events\OnMessageExecutionStartedEvent;

/**
 * Message execution started
 */
final class StartMessageExecutionListener
{
    /**
     * Execute event
     *
     * @param OnMessageExecutionStartedEvent $event
     *
     * @return void
     */
    public function __invoke(OnMessageExecutionStartedEvent $event): void
    {
        ApplicationLogger::debug(
            'messages',
            \sprintf('Execution message "%s" started', \get_class($event->getMessage()))
        );
    }
}