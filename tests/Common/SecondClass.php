<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Common;

/**
 *
 */
final class SecondClass extends FirstClass
{
    /**
     * @var string
     */
    private $secondClassValue = 'root';

    /**
     * @var string
     */
    private $secondClassPublicValue = 'abube';

    /**
     * @return string
     */
    public function secondClassValue(): string
    {
        return $this->secondClassValue;
    }

    /**
     * @return string
     */
    public function secondClassPublicValue(): string
    {
        return $this->secondClassPublicValue;
    }
}
