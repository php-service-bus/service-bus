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

/**
 * The incorrect namespace of the saga identifier class is specified
 */
class IdentifierClassNotFoundException extends SagaConfigurationException
{
    /**
     * @param string $specifiedNamespace
     */
    public function __construct(string $specifiedNamespace)
    {
        parent::__construct(
            \sprintf(
                'The incorrect namespace of the saga identifier class is specified ("%s")',
                $specifiedNamespace
            )
        );
    }
}
