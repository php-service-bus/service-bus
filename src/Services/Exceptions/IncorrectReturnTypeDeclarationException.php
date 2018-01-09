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

use React\Promise\PromiseInterface;

/**
 * Incorrect return type declaration
 */
class IncorrectReturnTypeDeclarationException extends \LogicException implements ServiceConfigurationExceptionInterface
{
    /**
     * @param \ReflectionMethod $method
     */
    public function __construct(\ReflectionMethod $method)
    {
        parent::__construct(
            \sprintf(
                'The "%s" method of the "%s" service must have a return value declaration "%s"',
                $method->getName(),
                $method->getDeclaringClass()->getName(),
                PromiseInterface::class
            )
        );
    }
}
