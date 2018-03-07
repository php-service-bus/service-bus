<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Annotations;

use Desperado\ServiceBus\ServiceBusExceptionInterface;

/**
 *
 */
class UnknownAnnotationPropertyException extends \LogicException implements ServiceBusExceptionInterface
{
    /**
     * UnknownAnnotationPropertyException constructor.
     *
     * @param string $propertyName
     * @param AbstractAnnotation $annotation
     */
    public function __construct(string $propertyName, AbstractAnnotation $annotation)
    {
        parent::__construct(
            \sprintf('Unknown property "%s" on annotation "%s"', $propertyName, \get_class($annotation))
        );
    }
}
