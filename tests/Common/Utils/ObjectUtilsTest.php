<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Tests\Common\Utils;

use Desperado\ConcurrencyFramework\Common\Utils\ObjectUtils;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ObjectUtilsTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function getClassName(): void
    {
        static::assertEquals('ObjectUtilsTest', ObjectUtils::getClassName($this));
    }

    /**
     * @test
     *
     * @return void
     */
    public function getObjectVars(): void
    {
        $stdClass = new \stdClass();
        $stdClass->field = 'value';
        $stdClass->field3 = new \stdClass();
        $stdClass->field3->qwerty = 'root';
        /** Will be ignored */
        $stdClass->field5 = $stdClass;

        $result = ObjectUtils::getObjectVars($stdClass);

        $expected = [
            'field'  => 'value',
            'field3' => [
                'qwerty' => 'root'
            ]
        ];

        static::assertEquals($expected, $result);
    }
}
