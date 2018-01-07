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
     * @param SagaService $sagaService
     */
    public function __construct(SagaService $sagaService)
    {
        parent::__construct($sagaService);
    }

    /**
     * @inheritdoc
     */
    public function applyOutboundMessageContext(OutboundMessageContext $outboundMessageContext): self
    {
        $self = new self($this->getSagaService());

        $self->outboundMessageContext = $outboundMessageContext;

        return $self;
    }

    /**
     * @inheritdoc
     */
    protected function getOutboundMessageContext(): ?OutboundMessageContext
    {
        return $this->outboundMessageContext;
    }
}
