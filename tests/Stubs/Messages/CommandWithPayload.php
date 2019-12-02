<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Stubs\Messages;

/**
 *
 */
final class CommandWithPayload
{
    private string $payload;

    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }

    public function payload(): string
    {
        return $this->payload;
    }
}
