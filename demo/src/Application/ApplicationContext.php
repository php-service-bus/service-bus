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

use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\EventSourcing\Service\EventSourcingService;
use Desperado\Saga\Service\SagaService;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;

/**
 * Application-level context
 */
class ApplicationContext extends AbstractExecutionContext
{
    /**
     * Outbound context
     *
     * @var OutboundMessageContext
     */
    private $outboundMessageContext;

    /**
     * Event sourcing service
     *
     * @var EventSourcingService
     */
    private $eventSourcingService;

    /**
     * @param SagaService          $sagaService
     * @param EventSourcingService $eventSourcingService
     */
    public function __construct(SagaService $sagaService, EventSourcingService $eventSourcingService)
    {
        parent::__construct($sagaService);

        $this->eventSourcingService = $eventSourcingService;
    }

    /**
     * @inheritdoc
     */
    public function applyOutboundMessageContext(OutboundMessageContextInterface $outboundMessageContext): self
    {
        $self = new self($this->getSagaService(), $this->getEventSourcingService());

        $self->outboundMessageContext = $outboundMessageContext;

        return $self;
    }

    /**
     * Get event sourcing service
     *
     * @return EventSourcingService
     */
    public function getEventSourcingService(): EventSourcingService
    {
        return $this->eventSourcingService;
    }

    /**
     * @inheritdoc
     */
    public function getOutboundMessageContext(): ?OutboundMessageContext
    {
        return $this->outboundMessageContext;
    }
}
