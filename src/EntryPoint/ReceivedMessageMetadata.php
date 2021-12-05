<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\EntryPoint;

use ServiceBus\Common\Context\IncomingMessageMetadata;

final class ReceivedMessageMetadata implements IncomingMessageMetadata
{
    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $messageId;

    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $traceId;

    /**
     * @psalm-var array<non-empty-string, string|int|float|bool|null>
     *
     * @var array
     */
    private $variables;

    /**
     * @psalm-param non-empty-string                                    $messageId
     * @psalm-param non-empty-string                                    $traceId
     * @psalm-param array<non-empty-string, string|int|float|bool|null> $variables
     */
    public function __construct(string $messageId, string $traceId, array $variables)
    {
        $this->messageId = $messageId;
        $this->traceId   = $traceId;
        $this->variables = $variables;
    }

    public function messageId(): string
    {
        return $this->messageId;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function variables(): array
    {
        return $this->variables;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->variables);
    }

    public function get(string $key, float|bool|int|string|null $default = null): string|int|float|bool|null
    {
        return $this->variables[$key] ?? $default;
    }
}
