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

namespace Desperado\ServiceBus\Tests\Endpoint;

use function Amp\Promise\wait;
use Desperado\ServiceBus\Endpoint\ApplicationTransportEndpoint;
use Desperado\ServiceBus\Endpoint\DeliveryOptions;
use Desperado\ServiceBus\Infrastructure\Retry\OperationRetryWrapper;
use Desperado\ServiceBus\Infrastructure\Retry\RetryOptions;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\BunnyRabbitMQ\BunnyRabbitMqTransport;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class ApplicationTransportEndpointTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function retryOnFailure(): void
    {
        $timestamp = \time();
        $throwable = null;

        try
        {
            $endpoint = new ApplicationTransportEndpoint(
                new BunnyRabbitMqTransport(AmqpConnectionConfiguration::createLocalhost()),
                new AmqpTransportLevelDestination('qwerty', 'root'),
                null,
                new OperationRetryWrapper(new RetryOptions(3, 1000))
            );

            $options = new DeliveryOptions();
            $options->traceId = 'ssss';

            wait($endpoint->delivery(new SecondEmptyCommand(), $options));
        }
        catch(\Throwable $throwable)
        {

        }

        static::assertNotNull($throwable);
        static::assertEquals(
            'There is no active connection to the message broker. You must call the connect method',
            $throwable->getMessage()
        );

        $duration = \time() - $timestamp;

        static::assertGreaterThanOrEqual(2, $duration);
    }
}
