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
 * Incorrect http request method specified
 */
class IncorrectHttpMethodException extends \LogicException implements ServiceConfigurationExceptionInterface
{
    /**
     * @param string $handlerPath
     * @param string $httpMethod
     * @param array  $availableChoices
     */
    public function __construct(string $handlerPath, string $httpMethod, array $availableChoices)
    {
        parent::__construct(
            \sprintf(
                'Invalid http request type is specified (%s). Valid types: %s ("%s" handler)',
                $httpMethod, \implode(', ', \array_values($availableChoices)), $handlerPath
            )
        );
    }
}
