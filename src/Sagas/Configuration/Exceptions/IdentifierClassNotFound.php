<?php

/**
 * Sagas implementation
 *
 * @see     http://microservices.io/patterns/data/saga.html
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas\Configuration\Exceptions;

/**
 * Identifier class not found
 */
final class IdentifierClassNotFound extends \RuntimeException
{
    /**
     * @param string $specifiedClass
     * @param string $sagaClass
     */
    public function __construct(string $specifiedClass, string $sagaClass)
    {
        parent::__construct(
            \sprintf('Identifier class "%s" specified in the saga "%s" not found', $specifiedClass, $sagaClass)
        );
    }
}
