<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Exceptions;

use Desperado\Domain\Identity\IdentityInterface;
use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * Saga closed exception
 */
class SagaIsClosedException extends \RuntimeException implements ServiceBusExceptionInterface
{
    /**
     * @param IdentityInterface $identity
     * @param int               $currentStatus
     */
    public function __construct(IdentityInterface $identity, int $currentStatus)
    {
        parent::__construct(
            \sprintf(
                'Saga "%s" is closed with status "%s"',
                $identity->toString(),
                $currentStatus
            )
        );
    }
}
