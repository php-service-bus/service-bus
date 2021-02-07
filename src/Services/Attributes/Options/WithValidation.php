<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Attributes\Options;

/**
 * @psalm-immutable
 */
final class WithValidation
{
    /**
     * @psalm-readonly
     *
     * @var string[]
     */
    public $groups = [];

    /**
     * @param string[] $groups
     */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }
}
