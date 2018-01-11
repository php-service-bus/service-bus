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
     * Create for message handlers
     *
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return InvalidHandlerArgumentsCountException
     */
    public static function createForMessageHandler(\ReflectionMethod $reflectionMethod): self
    {
        return new self(
            \sprintf(
                'The "%s:%s" handler contains an incorrect number of arguments. Minimum quantity: 2 '
                . '(AbstractCommand $command (or AbstractEvent $event), ApplicationExecutionContext $context '
                . '(extends AbstractExecutionContext))',
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName()
            )
        );
    }

    /**
     * Create for error handlers
     *
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return InvalidHandlerArgumentsCountException
     */
    public static function createForErrorHandler(\ReflectionMethod $reflectionMethod): self
    {
        return new self(
            \sprintf(
                'The "%s:%s" handler contains an incorrect number of arguments. Minimum quantity: 1 '
                . '(UnfulfilledPromiseData $unfulfilledPromiseData)',
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName()
            )
        );
    }
}
