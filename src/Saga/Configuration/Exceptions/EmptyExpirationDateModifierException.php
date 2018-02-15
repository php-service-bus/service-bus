<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Configuration\Exceptions;

use Desperado\ServiceBus\Annotations\Sagas\Saga;

/**
 *  The modifier of the expiration date of the saga is not specified
 */
class EmptyExpirationDateModifierException extends SagaConfigurationException
{
    public function __construct()
    {
        parent::__construct(
            \sprintf(
                'The modifier of the expiration date of the saga is not specified. '
                . 'Please specify the value "expireDateModifier" in %s annotation',
                Saga::class
            )
        );
    }
}
