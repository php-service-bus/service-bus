<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas\Exceptions;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 *
 */
final class InvalidSagaEventListenerMethod extends \RuntimeException
{
    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return self
     */
    public static function tooManyArguments(\ReflectionMethod $reflectionMethod): self
    {
        return new self(
            \sprintf(
                'There are too many arguments for the "%s:%s" method. A subscriber can only accept an argument: the class of the event he listens to',
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName()
            )
        );
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return self
     */
    public static function wrongEventArgument(\ReflectionMethod $reflectionMethod): self
    {
        return new self(
            \sprintf(
                'The event handler "%s:%s" should take as the first argument an object that implements the "%s"',
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName(),
                Event::class
            )
        );
    }
}
