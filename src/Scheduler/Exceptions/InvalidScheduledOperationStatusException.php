<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Exceptions;

/**
 *
 */
class InvalidScheduledOperationStatusException extends \LogicException implements SchedulerExceptionInterface
{
    /**
     * @param string $specifiedStatus
     * @param array  $availableChoices
     */
    public function __construct(string $specifiedStatus, array $availableChoices)
    {
        parent::__construct(
            \sprintf(
                'Invalid status specified ("%s"). Available choices: %s',
                $specifiedStatus, \implode(', ', $availableChoices)
            )
        );
    }
}
