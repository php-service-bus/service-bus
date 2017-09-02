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

namespace Desperado\Framework\Tests\Common\Serializer;

use Desperado\Framework\Common\Serializer\JsonSerializeHandler;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class JsonSerializeHandlerTest extends TestCase
{

    /**
     * @test
     *
     * @return void
     */
    public function arraySerialize(): void
    {
        static::assertEquals('{"key":"value"}', JsonSerializeHandler::serialize(['key' => 'value']));
    }

    /**
     * @test
     *
     * @return void
     */
    public function scalarSerialize(): void
    {
        static::assertEquals('"someString"',  JsonSerializeHandler::serialize('someString'));
    }

    /**
     * @test
     * @expectedException \Desperado\Framework\Common\Serializer\Exceptions\JsonSerializationException
     * @expectedExceptionMessage Syntax error
     *
     * @return void
     */
    public function invalidJsonUnserialize(): void
    {
         JsonSerializeHandler::unserialize('not valid json');
    }

    /**
     * @test
     *
     * @return void
     */
    public function arrayUnserialize(): void
    {
        $expected = ['key' => 'value'];

        static::assertEquals(
            $expected,
             JsonSerializeHandler::unserialize( JsonSerializeHandler::serialize(['key' => 'value']))
        );
    }

    /**
     * @test
     *
     * @return void
     * @expectedException \Desperado\Framework\Common\Serializer\Exceptions\JsonSerializationException
     * @expectedExceptionMessage Malformed UTF-8 characters, possibly incorrectly encoded
     */
    public function serializeWithBadCharset(): void
    {
         JsonSerializeHandler::serialize(['key' => iconv('UTF-8', 'Windows-1251', 'значение')]);
    }
}
