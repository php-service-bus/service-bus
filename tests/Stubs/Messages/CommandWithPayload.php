<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Stubs\Messages;

use Desperado\ServiceBus\Common\Contract\Messages\Command;

/**
 *
 */
final class CommandWithPayload implements Command
{
    /**
     * @var string
     */
    private $payload;

    /**
     * @param string $payload
     */
    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function payload(): string
    {
        return $this->payload;
    }
}
