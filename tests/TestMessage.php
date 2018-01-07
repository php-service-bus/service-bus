<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests;

use Desperado\Domain\Message\AbstractMessage;

/**
 *
 */
class TestMessage extends AbstractMessage
{
    /**
     * @Assert\Type("string")
     *
     * @var string
     */
    protected $stringExpectedValue;

    /**
     * @Assert\Type("numeric")
     *
     * @var string|int|float
     */
    protected $numericExpectedValue;
}
