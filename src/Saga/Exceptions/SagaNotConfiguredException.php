<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Exceptions;

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 *
 */
class SagaNotConfiguredException extends \LogicException implements ServiceBusExceptionInterface
{
    /**
     * @param string $sagaNamespace
     */
    public function __construct(string $sagaNamespace)
    {
        parent::__construct(
            \sprintf('Saga "%s" not configured. Use `configure()` method', $sagaNamespace)
        );
    }
}
