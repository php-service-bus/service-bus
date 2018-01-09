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
 * Incorrect parameters specified in the annotation
 */
class IncorrectAnnotationDataException extends \LogicException implements ServiceConfigurationExceptionInterface
{

}
