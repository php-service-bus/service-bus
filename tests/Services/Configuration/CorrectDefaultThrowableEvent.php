<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Services\Configuration;

use ServiceBus\Services\Contracts\ExecutionFailedEvent;

/**
 *
 */
final class CorrectDefaultThrowableEvent implements ExecutionFailedEvent
{
    /**
     * @inheritDoc
     */
    public static function create(string $correlationId, string $errorMessage): ExecutionFailedEvent
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function correlationId(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function errorMessage(): string
    {
        return '';
    }
}
