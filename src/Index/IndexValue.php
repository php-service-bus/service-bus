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

namespace Desperado\ServiceBus\Index;

use Desperado\ServiceBus\Index\Exceptions\EmptyValuesNotAllowed;
use Desperado\ServiceBus\Index\Exceptions\InvalidValueType;

/**
 * The value stored in the index
 */
final class IndexValue
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     *
     * @return self
     *
     * @throws \Desperado\ServiceBus\Index\Exceptions\InvalidValueType
     * @throws \Desperado\ServiceBus\Index\Exceptions\EmptyValuesNotAllowed
     */
    public static function create($value): self
    {
        self::assertIsScalar($value);
        self::assertNotEmpty($value);

        $self = new self();

        $self->value = $value;

        return $self;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Index\Exceptions\EmptyValuesNotAllowed
     */
    private static function assertNotEmpty($value): void
    {
        if('' === (string) $value)
        {
            throw new EmptyValuesNotAllowed('Value can not be empty');
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Index\Exceptions\InvalidValueType
     */
    private static function assertIsScalar($value): void
    {
        if(false === \is_scalar($value))
        {
            throw new InvalidValueType(
                \sprintf('The value must be of type "scalar". "%s" passed', \gettype($value))
            );
        }
    }

    private function __construct()
    {

    }
}
