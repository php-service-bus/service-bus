<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Alerting;

/**
 * @psalm-immutable
 */
final class AlertMessage
{
    /** @var string */
    public $content;

    /**
     * @psalm-param array<string, string|float|int> $placeholders
     */
    public function __construct(string $template, array $placeholders = [])
    {
        $this->content = \str_replace(\array_keys($placeholders), \array_values($placeholders), $template);
    }
}
