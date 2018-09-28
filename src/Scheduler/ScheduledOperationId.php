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

namespace Desperado\ServiceBus\Scheduler;

use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed;

/**
 *
 */
final class ScheduledOperationId
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     */
    public function __construct(string $value)
    {
        if('' === $value)
        {
            throw new EmptyScheduledOperationIdentifierNotAllowed();
        }

        $this->value = $value;
    }

    /**
     * @return self
     */
    public static function new(): self
    {
        return new self(uuid());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
