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

use Amp\Loop;
use function Amp\Promise\wait;
use Bunny\Channel;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Transport\Amqp\AmqpExchange;
use Desperado\ServiceBus\Transport\Amqp\AmqpQueue;
use Desperado\ServiceBus\Transport\Amqp\Bunny\AmqpBunny;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\QueueBind;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 *
 */
final class AmqpBunnyConsumerTest extends TestCase
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

        $queue    = AmqpQueue::default('qwerty.message');
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
    public function successConsume(): void
    {
        $publisher = $this->transport->createPublisher();

        $message = new CommandWithPayload('payload');

        $envelope = $publisher->createEnvelope($message, ['headerKey' => 'headerValue']);

        wait($publisher->send(new Destination('qwerty', 'testing'), $envelope));

        $this->transport
            ->createConsumer(AmqpQueue::default('qwerty.message'))
            ->listen(
                function(IncomingEnvelope $incomingEnvelope)
                {
                    try
                    {
                        static::assertNotEmpty($incomingEnvelope->operationId());
                        static::assertTrue(Uuid::isValid($incomingEnvelope->operationId()));

                        static::assertNotEmpty($incomingEnvelope->headers());
                        static::assertCount(3, $incomingEnvelope->headers());
                        static::assertEquals([
                            'headerKey'        => 'headerValue',
                            'content-type'     => 'application/json',
                            'content-encoding' => 'UTF-8',
                        ], $incomingEnvelope->headers());

                        /** @noinspection UnnecessaryAssertionInspection */
                        static::assertInstanceOf(CommandWithPayload::class, $incomingEnvelope->denormalized());
                    }
                    catch(\Throwable $throwable)
                    {
                        echo $throwable->getMessage(), \PHP_EOL;
                        exit(1);
                    }

                    yield $this->transport->close();
                }
            );

        Loop::run();
    }
}
