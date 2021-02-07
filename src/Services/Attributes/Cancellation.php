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
final class Cancellation
{
    /**
     * Operation timeout (in seconds).
     *
     * @var int
     */
    public $timeout;

    public function __construct(int $timeout)
    {
        $this->timeout = $timeout;
    }
}
