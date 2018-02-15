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
 * Cant find Saga annotation
 */
class SagaAnnotationNotAppliedException extends SagaConfigurationException
{
    /**
     * @param string $sagaNamespace
     */
    public function __construct(string $sagaNamespace)
    {
        parent::__construct(
            \sprintf(
                'Cant find "%s" annotation for saga "%s"', Saga::class, $sagaNamespace
            )
        );
    }
}
