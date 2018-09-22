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

namespace Desperado\ServiceBus\Tests\Transport\Amqp\Bunny;

use function Amp\Promise\wait;
use Bunny\Channel;
use Bunny\Message;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Transport\Amqp\AmqpExchange;
use Desperado\ServiceBus\Transport\Amqp\AmqpQueue;
use Desperado\ServiceBus\Transport\Amqp\Bunny\AmqpBunny;
use Desperado\ServiceBus\Transport\QueueBind;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class BunnyPublisherTest extends TestCase
{
    /**
     * @var AmqpBunny
     */
    private $transport;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = new AmqpBunny(AmqpConnectionConfiguration::createLocalhost());


        $queue = AmqpQueue::default('qwerty.message');
        $exchange = AmqpExchange::direct('qwerty');

        $this->transport->createTopic($exchange);
        $this->transport->createQueue($queue);
        $this->transport->bindQueue(new QueueBind($queue, $exchange, 'testing'));

        $this->channel = readReflectionPropertyValue($this->transport, 'channel');
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        try
        {
            /** @var Channel $channel */
            $channel = readReflectionPropertyValue($this->transport, 'channel');

            wait($channel->exchangeDelete('qwerty'));
            wait($channel->queueDelete('qwerty.message'));

            wait($this->transport->close());

            unset($this->transport);
        }
        catch(\Throwable $throwable)
        {

        }
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successPublish(): void
    {
        $publisher = $this->transport->createPublisher();

        $message = new CommandWithPayload('payload');

        $envelope = $publisher->createEnvelope($message, ['headerKey' => 'headerValue']);

        wait($publisher->send(new Destination('qwerty', 'testing'), $envelope));

        /** @var \Bunny\Message $incomingMessage */
        $incomingMessage = wait($this->channel->get('qwerty.message'));

        static::assertNotNull($message);
        static::assertInstanceOf(Message::class, $incomingMessage);

        $jsonDecodedMessage = \json_decode($incomingMessage->content, true);

        static::assertInternalType('array', $jsonDecodedMessage);
        static::assertNotEmpty($jsonDecodedMessage);
        static::assertArrayHasKey('message', $jsonDecodedMessage);
        static::assertArrayHasKey('namespace', $jsonDecodedMessage);
        static::assertArrayHasKey('payload', $jsonDecodedMessage['message']);
        static::assertEquals($message->payload(), $jsonDecodedMessage['message']['payload']);

    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function publishToWrongExchange(): void
    {
        $publisher = $this->transport->createPublisher();
        $promise = $publisher->send(
            new Destination('qwerty', 'root'),
            $publisher->createEnvelope(new CommandWithPayload('payload'))
        );

        wait($promise);

    }
}
