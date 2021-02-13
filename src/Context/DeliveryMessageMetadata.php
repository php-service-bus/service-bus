<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Context;

use ServiceBus\Common\Context\OutcomeMessageMetadata;

/**
 *
 */
final class DeliveryMessageMetadata implements OutcomeMessageMetadata
{
    /**
     * @var string
     */
    private $traceId;

    /**
     * @psalm-var array<string, string|int|float|bool|null>
     * @var array
     */
    private $variables;

    public static function create(string $traceId, array $variables = []): self
    {
        return new self($traceId, $variables);
    }

    public function with(string $key, float|bool|int|string|null $value): self
    {
        $variables       = $this->variables;
        $variables[$key] = $value;

        return new self($this->traceId, $variables);
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

    /**
     * @psalm-param array<string, string|int|float|bool|null> $variables
     */
    private function __construct(string $traceId, array $variables)
    {
        $this->traceId   = $traceId;
        $this->variables = $variables;
    }
}
