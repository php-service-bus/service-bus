<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Guard;

use Desperado\Domain\DateTime;
use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDateException;
use Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationIdException;

/**
 *
 */
class SchedulerGuard
{
    /**
     * @param string $id
     *
     * @return void
     *
     * @throws InvalidScheduledOperationIdException
     */
    public static function guardOperationId(string $id): void
    {
        if('' === $id)
        {
            throw new InvalidScheduledOperationIdException(
                'Invalid scheduled operation identifier'
            );
        }
    }

    /**
     * @param DateTime $dateTime
     *
     * @return void
     *
     * @throws InvalidScheduledOperationExecutionDateException
     */
    public static function guardOperationExecutionDate(DateTime $dateTime): void
    {
        $currentDate = DateTime::now();

        if($currentDate->toTimestamp() > $dateTime->toTimestamp())
        {
            throw new InvalidScheduledOperationExecutionDateException('Scheduled operation date must be greater then now');
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
