<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Contracts;

use ServiceBus\Common\Messages\Event;

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
