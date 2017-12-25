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

namespace Desperado\Framework;

use Desperado\CQRS\MessageBus\MessageBus;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\EventSourcing\Service\EventSourcingService;
use Desperado\Framework\Application\AbstractApplicationContext;
use Desperado\Saga\Service\SagaService;

/**
 * Message execution processor
 */
class MessageProcessor
{
    /**
     * Message bus
     *
     * @var MessageBus
     */
    private $messageBus;

    /**
     * Event sourcing service
     *
     * @var EventSourcingService
     */
    private $eventSourcingService;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * @param MessageBus           $messageBus
     * @param EventSourcingService $eventSourcingService
     * @param SagaService          $sagaService
     */
    public function __construct(
        MessageBus $messageBus,
        EventSourcingService $eventSourcingService,
        SagaService $sagaService
    )
    {
        $this->messageBus = $messageBus;
        $this->eventSourcingService = $eventSourcingService;
        $this->sagaService = $sagaService;
    }

    /**
     * Execute message
     *
     * @param AbstractMessage           $message
     * @param AbstractApplicationContext $context
     *
     * @return void
     */
    public function execute(AbstractMessage $message, AbstractApplicationContext $context): void
    {
        $this->messageBus->handle($message, $context);

        $this->sagaService->commitAll($context);
        $this->eventSourcingService->commitAll($context);
    }
}
