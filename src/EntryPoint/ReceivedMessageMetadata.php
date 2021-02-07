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

/**
 *
 */
final class ReceivedMessageMetadata implements IncomingMessageMetadata
{
    /**
     * @var string
     */
    private $messageId;

    /**
     * @psalm-var array<string, string|int|float|bool|null>
     * @var array
     */
    private $variables;

    public static function create(string $messageId, array $variables): self
    {
        return new self(
            messageId: $messageId,
            variables: $variables
        );
    }

    public function with(string $key, float|bool|int|string|null $value): self
    {
        $variables       = $this->variables;
        $variables[$key] = $value;

        return new self(
            messageId: $this->messageId,
            variables: $variables
        );
    }

    public function messageId(): string
    {
        return $this->messageId;
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

    /**
     * @psalm-param array<string, string|int|float|bool|null> $variables
     */
    private function __construct(string $messageId, array $variables)
    {
        $this->messageId = $messageId;
        $this->variables = $variables;
    }
}
