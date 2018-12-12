<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Stubs\Messages;

use Desperado\ServiceBus\Services\Contracts\ExecutionFailedEvent;

/**
 *
 */
final class ExecutionFailed implements ExecutionFailedEvent
{
    /**
     * Request Id
     *
     * @var string
     */
    private $correlationId;

    /**
     * Exception message
     *
     * @var string
     */
    private $reason;

    /**
     * @inheritDoc
     */
    public static function create(string $correlationId, string $errorMessage): ExecutionFailedEvent
    {
        $self = new self();

        $self->correlationId = $correlationId;
        $self->reason        = $errorMessage;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @inheritDoc
     */
    public function errorMessage(): string
    {
        return $this->reason;
    }
}
