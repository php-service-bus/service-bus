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

namespace Desperado\ServiceBus\Sagas\Exceptions;

use Desperado\ServiceBus\Sagas\SagaId;

/**
 *
 */
final class DuplicateSagaId extends \InvalidArgumentException
{
    /**
     * @param SagaId $id
     */
    public function __construct(SagaId $id)
    {
        parent::__construct(
            \sprintf('The saga with the identifier "%s:%s" already exists', (string) $id, \get_class($id))
        );
    }
}
