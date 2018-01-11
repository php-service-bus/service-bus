<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\KernelEvents;

use Symfony\Component\EventDispatcher\Event;

/**
 * The message bus has been successfully configured
 */
class MessageBusCompiledEvent extends Event
{
    public const EVENT_NAME = 'service_bus.kernel_events.message_bus_compiled';

    /**
     * Message bus tasks count
     *
     * @var int
     */
    private $taskCount;

    /**
     * @param int $taskCount
     */
    public function __construct(int $taskCount)
    {
        $this->taskCount = $taskCount;
    }

    /**
     * Get the number of messages in the bus
     *
     * @return int
     */
    public function getTaskCount(): int
    {
        return $this->taskCount;
    }
}
