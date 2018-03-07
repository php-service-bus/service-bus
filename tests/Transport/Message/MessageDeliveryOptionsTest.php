<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Tests\Transport\Message;

use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MessageDeliveryOptionsTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function create(): void
    {
        $options = MessageDeliveryOptions::create(
            'destinationKey',
            'routingKey',
            new ParameterBag()
        );

        static::assertEquals('destinationKey', $options->getDestination());
        static::assertEquals('routingKey', $options->getRoutingKey());
        static::assertEquals(new ParameterBag(), $options->getHeaders());

        static::assertTrue($options->destinationSpecified());
        static::assertTrue($options->routingKeySpecified());

        $newObject = $options->changeDestination('newDestinationKey');

        static::assertEquals('newDestinationKey', $newObject->getDestination());

        self::assertNotEquals(
            \spl_object_hash($options),
            \spl_object_hash($newObject)
        );
    }
}
