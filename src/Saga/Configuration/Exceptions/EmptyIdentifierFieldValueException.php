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
 * The field that contains the saga identifier must be specified
 */
class EmptyIdentifierFieldValueException extends SagaConfigurationException
{
    public function __construct()
    {
        parent::__construct(
            \sprintf(
                'The field that contains the saga identifier must be specified. '
                . 'Please specify the value "containingIdentifierProperty" in %s annotation',
                Saga::class
            )
        );
    }
}
