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

/**
 * Invalid number of arguments in the handler
 */
class InvalidHandlerArgumentsCountException extends \LogicException implements ServiceConfigurationExceptionInterface
{
    /**
     * @param \ReflectionMethod $reflectionMethod
     * @param int               $expectedParametersCount
     */
    public function __construct(\ReflectionMethod $reflectionMethod, int $expectedParametersCount)
    {
        parent::__construct(
            \sprintf(
                'The "%s:%s" handler contains an incorrect number of arguments. Maximum quantity: %d',
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName(),
                $expectedParametersCount
            )
        );
    }
}
