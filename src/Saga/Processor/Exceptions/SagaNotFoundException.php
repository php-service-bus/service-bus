<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Processor\Exceptions;

use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * Could not find saga
 */
class SagaNotFoundException extends \RuntimeException implements ServiceBusExceptionInterface
{
    /**
     * @param AbstractSagaIdentifier $id
     */
    public function __construct(AbstractSagaIdentifier $id)
    {
        parent::__construct(
            \sprintf('Saga with identifier "%s" not found', $id->toCompositeIndex())
        );
    }
}
