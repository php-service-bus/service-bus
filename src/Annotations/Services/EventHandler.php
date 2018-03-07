<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services;

use Desperado\ServiceBus\Annotations\AbstractAnnotation;
use Desperado\ServiceBus\Annotations\Services\Traits\LoggerChannelTrait;

/**
 * Annotation indicating to the event handler (listener)
 *
 * @Annotation
 * @Target("METHOD")
 */
final class EventHandler extends AbstractAnnotation implements MessageHandlerAnnotationInterface
{
    use LoggerChannelTrait;
}
