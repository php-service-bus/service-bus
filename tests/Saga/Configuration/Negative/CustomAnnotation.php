<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Configuration\Negative;

use Desperado\ServiceBus\Annotations\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class CustomAnnotation extends AbstractAnnotation
{

}
