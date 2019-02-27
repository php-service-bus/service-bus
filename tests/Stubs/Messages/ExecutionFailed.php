<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Stubs\Messages;

use ServiceBus\Services\Contracts\ExecutionFailedEvent;

/**
 *
 */
final class ExecutionFailed implements ExecutionFailedEvent
{
    /**
     * Request Id.
     *
     * @var string
     */
    private $correlationId;

    /**
     * Exception message.
     *
     * @var string
     */
    private $reason;

    /**
     * {@inheritdoc}
     */
    public static function create(string $correlationId, string $errorMessage): ExecutionFailedEvent
    {
        $self = new self();

        $self->correlationId = $correlationId;
        $self->reason        = $errorMessage;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * {@inheritdoc}
     */
    public function errorMessage(): string
    {
        return $this->reason;
    }
}
