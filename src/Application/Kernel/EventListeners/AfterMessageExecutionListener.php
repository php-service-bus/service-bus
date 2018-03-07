<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Kernel\EventListeners;

use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Application\Kernel\Events\MessageProcessingCompletedEvent;

/**
 * Called after message is executed
 */
final class AfterMessageExecutionListener
{
    /**
     * Sagas service
     *
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @param SagaProvider $sagaProvider
     */
    public function __construct(SagaProvider $sagaProvider)
    {
        $this->sagaProvider = $sagaProvider;
    }

    /**
     * Message processing completed
     *
     * @param MessageProcessingCompletedEvent $event
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Saga\Store\Exceptions\DuplicateSagaException
     * @throws \Desperado\ServiceBus\Saga\Exceptions\CommitSagaFailedException
     */
    public function onComplete(MessageProcessingCompletedEvent $event): void
    {
        $this->sagaProvider->flush($event->getExecutionContext());
    }
}
