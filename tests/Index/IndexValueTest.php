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

namespace Desperado\ServiceBus\Tests\Index;

use Desperado\ServiceBus\Index\IndexValue;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class IndexValueTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Index\Exceptions\InvalidValueType
     * @expectedExceptionMessage The value must be of type "scalar". "object" passed
     *
     * @return void
     */
    public function createWithWrongType(): void
    {
        IndexValue::create(
            static function(): void
            {

            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Index\Exceptions\EmptyValuesNotAllowed
     * @expectedExceptionMessage Value can not be empty
     *
     * @return void
     */
    public function createWithEmptyValue(): void
    {
        IndexValue::create('');
    }

    /**
     * @test
     *
     * @return void
     */
    public function successCreate(): void
    {
        IndexValue::create(0);
    }
}
