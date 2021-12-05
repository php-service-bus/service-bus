<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Alerting;

/**
 * @psalm-immutable
 */
final class AlertMessage
{
    /**
     * @psalm-readonly
     * @psalm-var non-empty-string
     *
     * @var string
     */
    public $content;

    /**
     * @psalm-param non-empty-string                $template
     * @psalm-param array<string, string|float|int> $placeholders
     */
    public function __construct(string $template, array $placeholders = [])
    {
        /** @psalm-var non-empty-string $preparedContent */
        $preparedContent = \str_replace(\array_keys($placeholders), \array_values($placeholders), $template);

        $this->content = $preparedContent;
    }
}
