<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Configuration;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Services\Exceptions as ServicesExceptions;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * The helper for validating the handlers
 */
class ConfigurationGuard
{
    /**
     * Assert handlers return declaration type is correct
     *
     * @param \ReflectionMethod $method
     *
     * @return void
     *
     * @throws ServicesExceptions\IncorrectReturnTypeDeclarationException
     */
    public static function guardHandlerReturnDeclaration(\ReflectionMethod $method): void
    {
        if(false === $method->hasReturnType())
        {
            return;
        }

        $returnDeclarationType = $method->getReturnType()->getName();

        if('void' === $returnDeclarationType)
        {
            return;
        }

        if(PromiseInterface::class !== $returnDeclarationType && Promise::class !== $returnDeclarationType)
        {
            throw new ServicesExceptions\IncorrectReturnTypeDeclarationException($method);
        }
    }

    /**
     * Checking the number of arguments of the message handler
     *
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return void
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentsCountException
     */
    public static function guardMessageHandlerNumberOfParametersValid(\ReflectionMethod $reflectionMethod): void
    {
        if(2 > $reflectionMethod->getNumberOfRequiredParameters())
        {
            throw ServicesExceptions\InvalidHandlerArgumentsCountException::createForMessageHandler($reflectionMethod);
        }
    }

    /**
     * Assert context argument is valid
     *
     * @param \ReflectionMethod    $reflectionMethod
     * @param \ReflectionParameter $parameter
     *
     * @return void
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentException
     */
    public static function guardContextValidArgument(
        \ReflectionMethod $reflectionMethod,
        \ReflectionParameter $parameter
    )
    {
        if(
            null === $parameter->getClass() ||
            false === $parameter->getClass()->isSubclassOf(AbstractExecutionContext::class)
        )
        {
            throw new ServicesExceptions\InvalidHandlerArgumentException(
                \sprintf(
                    'The second argument to the handler "%s:%s" must be instanceof the "%s"',
                    $reflectionMethod->getDeclaringClass()->getName(),
                    $reflectionMethod->getName(),
                    AbstractExecutionContext::class
                )
            );
        }
    }

    /**
     * Assert message type is correct
     *
     * @param \ReflectionMethod    $reflectionMethod
     * @param \ReflectionParameter $parameter
     * @param int                  $argumentPosition
     *
     * @return void
     *
     * @throws ServicesExceptions\InvalidHandlerArgumentException
     */
    public static function guardValidMessageArgument(
        \ReflectionMethod $reflectionMethod,
        \ReflectionParameter $parameter,
        int $argumentPosition
    ): void
    {
        if(
            null === $parameter->getClass() ||
            false === $parameter->getClass()->isSubclassOf(AbstractMessage::class)
        )
        {
            throw new ServicesExceptions\InvalidHandlerArgumentException(
                \sprintf(
                    'The %d argument to the handler "%s:%s" must be instanceof the "%s" (%s specified)',
                    $argumentPosition,
                    $reflectionMethod->getDeclaringClass()->getName(),
                    $reflectionMethod->getName(),
                    AbstractMessage::class,
                    null !== $parameter->getClass()
                        ? $parameter->getClass()->getName()
                        : 'n/a'
                )
            );
        }
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
