<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Services\Attributes\Options;

final class WithValidation
{
    /**
     * @psalm-var list<non-empty-string>
     *
     * @var string[]
     */
    public array $groups = [];

    /**
     * @psalm-param list<non-empty-string> $groups
     */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }
}
