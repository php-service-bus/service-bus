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
     * @psalm-var array<string, string|int|float|bool|null>
     * @var array
     */
    private $variables;

    public static function create(array $variables = []): self
    {
        return new self($variables);
    }

    public function with(string $key, float|bool|int|string|null $value): self
    {
        $variables       = $this->variables;
        $variables[$key] = $value;

        return new self($variables);
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
    private function __construct(array $variables)
    {
        $this->variables = $variables;
    }
}
