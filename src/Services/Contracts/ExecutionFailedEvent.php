<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Contracts;

/**
 * Error processing message.
 */
interface ExecutionFailedEvent
{
    public static function create(string $correlationId, string $errorMessage): self;

    /**
     * Receive request correlation id (Message trace id).
     */
    public function correlationId(): string;

    /**
     * Receive error message.
     */
    public function errorMessage(): string;
}
