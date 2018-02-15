<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Exceptions;

use Desperado\ServiceBus\Annotations\Services\Service;

/**
 * The class level annotation is not found in the service
 */
class ServiceClassAnnotationNotFoundException extends \LogicException implements ServiceConfigurationExceptionInterface
{
    /**
     * @param string $serviceClass
     */
    public function __construct(string $serviceClass)
    {
        parent::__construct(
            \sprintf(
                'The class level annotation is not found in the "%s" service. It is necessary to add the annotation "%s"',
                $serviceClass, Service::class
            )
        );
    }
}
