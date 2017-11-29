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

namespace Desperado\Framework\Backend\Queue;

use Bunny\Channel;
use Bunny\Message;
use Desperado\CQRS\Context\DeliveryContextInterface;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\ParameterBag;
use Desperado\Framework\Application\ApplicationLogger;

/**
 * RabbitMQ execution context
 */
class RabbitMqDaemonContext implements DeliveryContextInterface
{
    protected const LOG_CHANNEL_NAME = 'rabbitMqContext';

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $serializer;

    /**
     * Exchange ID
     *
     * @var string
     */
    private $exchange;

    /**
     * AMQP channel
     *
     * @var Channel
     */
    private $channel;

    /**
     * Routing key (client ID)
     *
     * @var string
     */
    private $routingKey;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Message metadata (headers)
     *
     * @var ParameterBag
     */
    private $messageMetadata;

    /**
     * @param Message                    $incoming
     * @param Channel                    $channel
     * @param MessageSerializerInterface $serializer
     * @param Environment                $environment
     */
    public function __construct(
        Message $incoming,
        Channel $channel,
        MessageSerializerInterface $serializer,
        Environment $environment
    )
    {
        $this->exchange = $incoming->exchange;
        $this->routingKey = $incoming->routingKey;
        $this->messageMetadata = new ParameterBag(
            true === \is_array($incoming->headers)
                ? $incoming->headers
                : []
        );
        $this->channel = $channel;
        $this->serializer = $serializer;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function getMessageMetadata(): ParameterBag
    {
        return $this->messageMetadata;
    }

    /**
     * @inheritdoc
     */
    public function delivery(AbstractMessage $message, DeliveryOptions $deliveryOptions = null): void
    {
        $deliveryOptions = $deliveryOptions ?? new DeliveryOptions();

        $message instanceof AbstractCommand
            ? $this->send($message, $deliveryOptions)
            /** @var AbstractEvent $message */
            : $this->publish($message, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function send(AbstractCommand $command, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($deliveryOptions, $command);
    }

    /**
     * @inheritdoc
     */
    public function publish(AbstractEvent $event, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($deliveryOptions, $event);
        $this->publishMessage(
            $deliveryOptions->changeDestination(
                \sprintf('%s.events', $this->exchange)
            ),
            $event
        );
    }

    /**
     * Send message to broker
     *
     * @param  DeliveryOptions $deliveryOptions
     * @param AbstractMessage $message
     *
     * @return void
     */
    private function publishMessage(DeliveryOptions $deliveryOptions, AbstractMessage $message): void
    {
        $destination = '' !== $deliveryOptions->getDestination()
            ? $deliveryOptions->getDestination()
            : $this->exchange;

        $serializedMessage = $this->serializer->serialize($message);

        $messageHeaders = $deliveryOptions->getHeaders();

        $messageHeaders->set('fromHost', \gethostname());
        $messageHeaders->set('daemon', 'rabbitMQ');

        $this->channel
            ->exchangeDeclare($destination, 'direct', true)
            ->then(
                function() use ($destination, $serializedMessage, $messageHeaders, $message)
                {
                    if(true === $this->environment->isDebug())
                    {
                        ApplicationLogger::debug(
                            self::LOG_CHANNEL_NAME,
                            \sprintf(
                                '%s "%s" to "%s" exchange with routing key "%s". Message data: %s (with headers "%s")',
                                $message instanceof AbstractCommand
                                    ? 'Send message'
                                    : 'Publish event',
                                \get_class($message),
                                $destination,
                                $this->routingKey,
                                $serializedMessage,
                                \urldecode(\http_build_query($messageHeaders->all()))
                            )
                        );
                    }

                    return $this->channel->publish(
                        $serializedMessage,
                        $messageHeaders->all(),
                        $destination,
                        $this->routingKey
                    );
                },
                function(\Throwable $throwable)
                {
                    ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable);
                }
            );
    }
}
