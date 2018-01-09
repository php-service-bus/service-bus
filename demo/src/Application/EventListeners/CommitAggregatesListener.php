<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Application;

use Desperado\EventSourcing\Service\EventSourcingService;
use Desperado\ServiceBus\KernelEvents\MessageProcessingCompletedEvent;

/**
 * Listener, which will be called to save changes to the aggregates
 */
class CommitAggregatesListener
{
    /**
     * Event sourcing service
     *
     * @var EventSourcingService
     */
    private $eventSourcingService;

    /**
     * @param EventSourcingService $eventSourcingService
     */
    public function __construct(EventSourcingService $eventSourcingService)
    {
        $this->eventSourcingService = $eventSourcingService;
    }

    /**
     * Message execution finished
     *
     * @param MessageProcessingCompletedEvent $event
     *
     * @return void
     */
    public function onComplete(MessageProcessingCompletedEvent $event): void
    {
        $this->eventSourcingService->commitAll($event->getExecutionContext());
    }
}
