<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageExecutor;

use ServiceBus\Services\Contracts\ExecutionFailedEvent;

/**
 *
 */
final class TestMessageExecutionFailed implements ExecutionFailedEvent
{
    /** @var string */
    private $correlationId;

    /** @var string */
    private $errorMessage;

    /**
     * @inheritDoc
     */
    public static function create(string $correlationId, string $errorMessage): ExecutionFailedEvent
    {
        return new self($correlationId, $errorMessage);
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
        return $this->errorMessage;
    }

    private function __construct(string $correlationId, string $errorMessage)
    {
        $this->correlationId = $correlationId;
        $this->errorMessage  = $errorMessage;
    }
}
