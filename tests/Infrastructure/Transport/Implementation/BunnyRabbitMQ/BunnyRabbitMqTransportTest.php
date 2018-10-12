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

namespace Desperado\ServiceBus\Tests\Infrastructure\Transport\Implementation\BunnyRabbitMQ;

use Amp\ByteStream\InMemoryStream;
use function Amp\Promise\wait;
use Bunny\Channel;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\BunnyRabbitMQ\BunnyIncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\BunnyRabbitMQ\BunnyRabbitMqTransport;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\QueueBind;
use Desperado\ServiceBus\Infrastructure\Transport\TopicBind;
use Desperado\ServiceBus\OutboundMessage\Destination;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class BunnyRabbitMqTransportTest extends TestCase
{
    /**
     * @var BunnyRabbitMqTransport
     */
    private $transport;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = new BunnyRabbitMqTransport(AmqpConnectionConfiguration::createLocalhost());
    }

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        /** @var Channel $channel */
        $channel = readReflectionPropertyValue($this->transport, 'channel');

        wait($channel->exchangeDelete('createExchange'));
        wait($channel->queueDelete('createQueue'));

        wait($channel->exchangeDelete('createExchange2'));
        wait($channel->queueDelete('createQueue2'));

        wait($this->transport->disconnect());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function connect(): void
    {
        wait($this->transport->connect());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createExchange(): void
    {
        wait($this->transport->createTopic(AmqpExchange::topic('createExchange')));

        static::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function createQueue(): void
    {
        wait($this->transport->createQueue(AmqpQueue::default('createQueue')));

        static::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function bindTopic(): void
    {
        wait(
            $this->transport->createTopic(
                AmqpExchange::topic('createExchange'),
                new TopicBind(
                    AmqpExchange::topic('createExchange2'),
                    'qwerty')
            )
        );

        static::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function bindQueue(): void
    {
        wait(
            $this->transport->createQueue(
                new AmqpQueue('createQueue'),
                new QueueBind(
                    AmqpExchange::topic('createExchange2'),
                    'qwerty')
            )
        );

        static::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function consume(): void
    {
        $exchange = AmqpExchange::direct('consume');
        $queue    = new AmqpQueue('consume.messages');

        wait($this->transport->createTopic($exchange));
        wait($this->transport->createQueue($queue, new QueueBind($exchange, 'consume')));

        $iterator = wait($this->transport->consume($queue));

        wait(
            $this->transport->send(
                new OutboundPackage(
                    new InMemoryStream('somePayload'),
                    ['key' => 'value'],
                    new Destination('consume', 'consume')
                )
            )
        );

        while(wait($iterator->advance()))
        {
            /** @var BunnyIncomingPackage $package */
            $package = $iterator->getCurrent();

            static::assertInstanceOf(BunnyIncomingPackage::class, $package);
            static::assertEquals('somePayload', wait($package->payload()->read()));
            static::assertEquals(['key' => 'value'], $package->headers());

            break;
        }
    }
}
