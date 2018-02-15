<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Store\Exceptions;

use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;

/**
 * Saga already exists
 */
class DuplicateSagaException extends SagaStoreException
{
    /**
     * @param AbstractSagaIdentifier $sagaIdentifier
     */
    public function __construct(AbstractSagaIdentifier $sagaIdentifier)
    {
        parent::__construct(
            \sprintf('Saga with identifier "%s" already exists', $sagaIdentifier->toCompositeIndex())
        );
    }
}
