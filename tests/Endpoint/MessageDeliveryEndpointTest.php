<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Endpoint;

use Monolog\Handler\TestHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use ServiceBus\Common\Context\Exceptions\MessageDeliveryFailed;
use ServiceBus\Endpoint\MessageDeliveryEndpoint;
use ServiceBus\Endpoint\Options\DefaultDeliveryOptions;
use function Amp\Promise\wait;
use function ServiceBus\Common\readReflectionPropertyValue;

/**
 *
 */
final class MessageDeliveryEndpointTest extends TestCase
{
    /** @var TestHandler */
    private $logHandler;

    /** @var EndpointTestTransport */
    private $transport;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logHandler->pushProcessor(new PsrLogMessageProcessor());

        $this->transport = new EndpointTestTransport();
    }

    /** @test */
    public function simpleCreate(): void
    {
        $endpoint = new MessageDeliveryEndpoint(__METHOD__, $this->transport, new EndpointTestDestination('qwerty'));

        static::assertSame(__METHOD__, $endpoint->name());
    }

    /** @test */
    public function withNewDeliveryDestination(): void
    {
        $endpoint = new MessageDeliveryEndpoint(__METHOD__, $this->transport, new EndpointTestDestination('qwerty'));
        $endpoint = $endpoint->withNewDeliveryDestination(new EndpointTestDestination('root'));

        /** @var EndpointTestDestination $destination */
        $destination = readReflectionPropertyValue($endpoint, 'destination');

        static::assertSame('root', $destination->data);
    }

    /** @test */
    public function deliveryWithError(): void
    {
        $this->transport->expectedDeliveryFailure();
        $this->expectException(MessageDeliveryFailed::class);

        $endpoint = new MessageDeliveryEndpoint(__METHOD__, $this->transport, new EndpointTestDestination('qwerty'));

        wait($endpoint->delivery(new FirstEmptyMessage, DefaultDeliveryOptions::create()));
    }
}
