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
 * Duplicate saga
 */
class SagaAlreadyExistsException extends \RuntimeException implements ServiceBusExceptionInterface
{
    /**
     * @param IdentityInterface $identity
     */
    public function __construct(IdentityInterface $identity)
    {
        parent::__construct(
            \sprintf('Saga "%s" already exists', $identity->toCompositeIndex())
        );
    }
}
