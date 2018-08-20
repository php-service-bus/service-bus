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

namespace Desperado\ServiceBus\Tests\Marshal\Serializer;

use Desperado\ServiceBus\Marshal\Serializer\SymfonyJsonSerializer;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SymfonyJsonSerializerTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Marshal\Serializer\Exceptions\SerializationFailed
     * @expectedExceptionMessage Malformed UTF-8 characters, possibly incorrectly encoded
     *
     * @return void
     */
    public function serializeObject(): void
    {
        (new SymfonyJsonSerializer())->serialize([
                'key' => \iconv('utf-8', 'windows-1251', 'тест')
            ]
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Marshal\Serializer\Exceptions\DeserializationFailed
     * @expectedExceptionMessage Incorrect json format: Syntax error
     *
     * @return void
     */
    public function incorrectJsonDeserialization(): void
    {
        (new SymfonyJsonSerializer())->unserialize('qwerty');
    }
}
