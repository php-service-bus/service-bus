<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Tests\Annotations;

use Desperado\ServiceBus\Annotations\AbstractAnnotation;

/**
 *
 */
class TestAnnotation extends AbstractAnnotation
{
    /**
     *
     *
     * @var mixed
     */
    protected $existsProperty;
}
