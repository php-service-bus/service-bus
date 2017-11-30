<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Backend\ReactRabbitMq;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Desperado\Domain\EntryPoint\DaemonInterface;
use Desperado\Domain\EntryPoint\EntryPointInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Framework\Application\ApplicationLogger;
use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use EventLoop\EventLoop;

/**
 *
 */
class ReactRabbitMqDaemon implements DaemonInterface
{
    /**
     * Application configuration
     *
     * @var ReactRabbitMqConfiguration
     */
    private $configuration;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Bunny client
     *
     * @var Client
     */
    private $client;

    /**
     * Subscriber
     *
     * @var ReactRabbitMqConsumer
     */
    private $subscriber;

    /**
     * Publisher
     *
     * @var ReactRabbitMqPublisher
     */
    private $publisher;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * @param ReactRabbitMqConfiguration $configuration
     * @param Environment                $environment
     * @param MessageSerializerInterface $messageSerializer
     */
    public function __construct(
        ReactRabbitMqConfiguration $configuration,
        Environment $environment,
        MessageSerializerInterface $messageSerializer
    )
    {
        $this->configuration = $configuration;
        $this->environment = $environment;
        $this->messageSerializer = $messageSerializer;

        \pcntl_async_signals(true);

        \pcntl_signal(\SIGINT, [$this, 'stop']);
        \pcntl_signal(\SIGTERM, [$this, 'stop']);
    }

    /**
     * @inheritdoc
     */
    public function run(EntryPointInterface $entryPoint, array $clients = []): void
    {
        $consumeCallable = function(Message $incoming, Channel $channel) use ($entryPoint)
        {
            EventLoop::getLoop()->futureTick(
                function() use ($incoming, $channel, $entryPoint)
                {
                    $this->handleMessage($entryPoint, $incoming, $channel);
                }
            );
        };

        $this
            ->getSubscriber()
            ->subscribe($entryPoint, $clients)
            ->then(
                function(ReactRabbitMqChannelData $channelData) use ($consumeCallable)
                {
                    $channelData
                        ->getChannel()
                        ->consume($consumeCallable, $channelData->getQueue());
                },
                function()
                {
                    $this->stop();
                }
            );

        EventLoop::getLoop()->run();
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        $this->getSubscriber()->unsubcribe();

        unset($this->client, $this->subscriber, $this->publisher);

        EventLoop::getLoop()->stop();

        exit(0);
    }

    /**
     * Handle received message
     *
     * @param EntryPointInterface $entryPoint
     * @param Message             $incoming
     * @param Channel             $channel
     *
     * @return void
     */
    private function handleMessage(EntryPointInterface $entryPoint, Message $incoming, Channel $channel): void
    {
        try
        {
            $this->logIncomeMessage($incoming);

            $entryPoint->handleMessage(
                $this->messageSerializer->unserialize($incoming->content),
                new ReactRabbitMqContext($this->getPublisher(), $incoming, $channel)
            );

            $channel->ack($incoming);
        }
        catch(\Throwable $throwable)
        {
            ApplicationLogger::throwable('throwable', $throwable);

            $throwable instanceof \LogicException
                ? $channel->nack($incoming)
                : $channel->ack($incoming);
        }
    }

    /**
     * Get subscriber
     *
     * @return ReactRabbitMqPublisher
     */
    private function getPublisher(): ReactRabbitMqPublisher
    {
        if(null === $this->publisher)
        {
            $this->publisher = new ReactRabbitMqPublisher(
                $this->environment,
                $this->messageSerializer,
                LoggerRegistry::getLogger('publisher')
            );
        }

        return $this->publisher;
    }

    /**
     * Get subscriber
     *
     * @return ReactRabbitMqConsumer
     */
    private function getSubscriber(): ReactRabbitMqConsumer
    {
        if(null === $this->subscriber)
        {
            $this->subscriber = new ReactRabbitMqConsumer(
                $this->getClient(),
                $this->configuration,
                LoggerRegistry::getLogger('consumer')
            );
        }

        return $this->subscriber;
    }

    /**
     * Get rabbit mq client
     *
     * @return Client
     */
    private function getClient(): Client
    {
        if(null === $this->client)
        {
            $this->client = new Client(
                EventLoop::getLoop(),
                $this->configuration->getConnectionConfig()->all()
            );
        }

        return $this->client;
    }

    /**
     * Push income message to log
     *
     * @param Message $incoming
     *
     * @return void
     */
    private function logIncomeMessage(Message $incoming): void
    {
        if(true === $this->environment->isDebug())
        {
            ApplicationLogger::debug(
                'income',
                \sprintf(
                    'Message received: "%s" with headers "%s"',
                    $incoming->content,
                    \urldecode(\http_build_query((array) $incoming->headers))
                )
            );
        }
    }
}
