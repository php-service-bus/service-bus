<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Commands;

use Desperado\Domain\Message\AbstractCommand;

/**
 * Fulfill the task of the scheduler
 *
 * @see SchedulerOperationEmittedEvent
 */
final class EmitSchedulerOperationCommand extends AbstractCommand
{
    /**
     * Scheduled operation identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Get scheduled operation identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
