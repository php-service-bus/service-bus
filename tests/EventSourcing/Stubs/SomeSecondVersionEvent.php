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

namespace Desperado\ServiceBus\Tests\EventSourcing\Stubs;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 *
 */
final class SomeSecondVersionEvent implements Event
{
    /**
     * @var string
     */
    private $someField;

    /**
     * @param string $someField
     */
    public function __construct(string $someField)
    {
        $this->someField = $someField;
    }

    /**
     * @return string
     */
    public function someField(): string
    {
        return $this->someField;
    }
}
