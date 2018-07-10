<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Router\Exceptions;

/**
 *
 */
final class EmptyDestinationTopicSpecified extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Topic name must be specified');
    }
}
