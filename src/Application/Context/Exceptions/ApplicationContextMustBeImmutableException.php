<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Context\Exceptions;

use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 * ExecutionContextInterface::applyOutboundMessageContext() Must return a NEW instance of the class
 */
class ApplicationContextMustBeImmutableException extends \LogicException implements ServiceBusExceptionInterface
{
    public function __construct()
    {
        parent::__construct(
            \sprintf(
                '%s::applyOutboundMessageContext() must return a new instance of the context class',
                ExecutionContextInterface::class
            )
        );
    }
}
