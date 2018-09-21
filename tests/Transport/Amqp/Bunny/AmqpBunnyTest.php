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
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Transport\Amqp\AmqpExchange;
use Desperado\ServiceBus\Transport\Amqp\AmqpQueue;
use Desperado\ServiceBus\Transport\Amqp\Bunny\AmqpBunny;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\TopicBind;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmqpBunnyTest extends TestCase
{
    /**
     * @var AmqpBunny
     */
    private $transport;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = new AmqpBunny(AmqpConnectionConfiguration::createLocalhost());
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

            wait($channel->exchangeDelete('createExchange'));
            wait($channel->queueDelete('createQueue'));

            wait($channel->exchangeDelete('createExchange2'));
            wait($channel->queueDelete('createQueue2'));

            $this->transport->close();

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
     */
    public function createExchange(): void
    {
        $this->transport->createTopic(AmqpExchange::topic('createExchange'));

        static::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function createQueue(): void
    {
        $this->transport->createQueue(AmqpQueue::default('createQueue'));

        static::assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function bindTopic(): void
    {
        $source      = AmqpExchange::topic('createExchange');
        $destination = AmqpExchange::topic('createExchange2');

        $this->transport->createTopic($source);
        $this->transport->createTopic($destination);

        $this->transport->bindTopic(new TopicBind($source, $destination, 'qwerty'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function bindQueue(): void
    {
        $destination = AmqpExchange::topic('createExchange2');
        $this->transport->createTopic($destination);

        $queue = AmqpQueue::default('createQueue');
        $this->transport->createQueue($queue);

        $this->transport->bindQueue(new QueueBind($queue, $destination, 'root'));
    }
}
