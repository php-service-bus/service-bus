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
 * The expiration date modifier is not valid
 */
class IncorrectExpirationDateModifierException extends SagaConfigurationException
{
    public function __construct()
    {
        parent::__construct(
            'The date of the saga\'s fading should be correct and greater than the current date'
        );
    }
}
