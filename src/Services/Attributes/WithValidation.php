<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Attributes;

/**
 *
 */
final class WithValidation
{
    /**
     * @var string[]
     */
    public $groups = [];
}
