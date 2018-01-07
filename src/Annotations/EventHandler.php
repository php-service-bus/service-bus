<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations;

use Desperado\Domain\Annotations\AbstractAnnotation;

/**
 * Annotation indicating to the event handler (listener)
 *
 * @Annotation
 * @Target("CLASS")
 */
class EventHandler extends AbstractAnnotation
{

}
