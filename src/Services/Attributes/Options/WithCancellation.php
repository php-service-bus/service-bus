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
final class WithCancellation
{
    /**
     * Operation timeout (in seconds).
     *
     * @psalm-readonly
     *
     * @var int
     */
    public $timeout;

    public function __construct(int $timeout)
    {
        $this->timeout = $timeout;
    }
}
