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
 * The namespace of the saga identifier class is not specified
 */
class EmptyIdentifierNamespaceException extends SagaConfigurationException
{
    public function __construct()
    {
        parent::__construct(
            \sprintf(
                'The namespace of the saga identifier class is not specified. '
                . 'Please specify the value "identifierNamespace" in %s annotation',
                Saga::class
            )
        );
    }
}
