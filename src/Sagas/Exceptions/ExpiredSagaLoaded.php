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
final class ExpiredSagaLoaded extends \RuntimeException
{
    /**
     * @param SagaId $id
     */
    public function __construct(SagaId $id)
    {
        parent::__construct(
            \sprintf('Unable to load the saga (ID: "%s") whose lifetime has expired', $id)
        );
    }
}
