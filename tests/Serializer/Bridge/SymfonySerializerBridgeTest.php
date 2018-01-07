<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Serializer\Bridge;

use Desperado\ServiceBus\Serializer\Bridge\SymfonySerializerBridge;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SymfonySerializerBridgeTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\Domain\Serializer\Exceptions\SerializationException
     * @expectedExceptionMessage Serialization fail: Unsupported serialization format specified ("xml"). Available
     *                           choices: json
     *
     * @return void
     */
    public function encodeWithWrongFormat(): void
    {
        $serializer = new SymfonySerializerBridge();
        $serializer->encode([], '', 'xml');
    }

    /**
     * @test
     * @expectedException \Desperado\Domain\Serializer\Exceptions\SerializationException
     * @expectedExceptionMessage Deserialization fail: Unsupported serialization format specified ("xml"). Available
     *                           choices: json
     *
     * @return void
     */
    public function decodeWithWrongFormat(): void
    {
        $serializer = new SymfonySerializerBridge();
        $serializer->decode('', 'xml', []);
    }

    /**
     * @test
     * @expectedException \Desperado\Domain\Serializer\Exceptions\SerializationException
     * @expectedExceptionMessage Serialization fail: Serialization format must be specified
     *
     * @return void
     */
    public function encodeWithEmptyFormat(): void
    {
        $serializer = new SymfonySerializerBridge();
        $serializer->encode([], '', '');
    }
}
