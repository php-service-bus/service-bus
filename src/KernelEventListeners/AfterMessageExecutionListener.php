<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\KernelEventListeners;

use Desperado\Saga\Service\SagaService;
use Desperado\ServiceBus\KernelEvents\MessageProcessingCompletedEvent;

/**
 * Called after message is executed
 */
class AfterMessageExecutionListener
{
    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * @param SagaService $sagaService
     */
    public function __construct(SagaService $sagaService)
    {
        $this->sagaService = $sagaService;
    }

    /**
     * Message processing completed
     *
     * @param MessageProcessingCompletedEvent $event
     *
     * @return void
     */
    public final function onComplete(MessageProcessingCompletedEvent $event): void
    {
        $this->sagaService->commitAll($event->getExecutionContext());
    }
}
