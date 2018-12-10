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

namespace Desperado\ServiceBus\Services\Contracts;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 * Error processing message
 */
interface ExecutionFailedEvent extends Event
{
    /**
     * @param string $correlationId
     * @param string $errorMessage
     *
     * @return self
     */
    public static function create(string $correlationId, string $errorMessage): self;

    /**
     * Receive request correlation id (Message trace id)
     *
     * @return string
     */
    public function correlationId(): string;

    /**
     * Receive error message
     *
     * @return string
     */
    public function errorMessage(): string;
}
