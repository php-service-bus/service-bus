<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Contract\Command;

use Desperado\Domain\Message\AbstractCommand;

/**
 * Cancel scheduler job
 *
 * @see SchedulerOperationCanceledEvent
 */
final class CancelSchedulerOperationCommand extends AbstractCommand
{
    /**
     * Scheduled operation identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Reason for canceling the scheduler job
     *
     * @var string|null
     */
    protected $reason;

    /**
     * Get scheduled operation identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get reason for canceling the scheduler job
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }
}
