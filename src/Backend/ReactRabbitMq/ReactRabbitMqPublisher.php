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

use Bunny\Channel;
use Bunny\Message;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\ThrowableFormatter;
use Psr\Log\LoggerInterface;

/**
 * RabbitMQ publisher
 */
class ReactRabbitMqPublisher
{
    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Environment                $environment
     * @param MessageSerializerInterface $messageSerializer
     * @param LoggerInterface            $logger
     */
    public function __construct(
        Environment $environment,
        MessageSerializerInterface $messageSerializer,
        LoggerInterface $logger
    )
    {
        $this->environment = $environment;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;
    }

    /**
     * @param Message         $incomingMessage
     * @param Channel         $channel
     * @param AbstractMessage $toPublishMessage
     * @param DeliveryOptions $deliveryOptions
     *
     * @return void
     */
    public function publish(
        Message $incomingMessage,
        Channel $channel,
        AbstractMessage $toPublishMessage,
        DeliveryOptions $deliveryOptions
    ): void
    {
        $destinationExchange = $this->locateDestinationExchange($incomingMessage, $deliveryOptions);

        $channel
            ->exchangeDeclare($destinationExchange, 'direct', true)
            ->then(
                function() use ($channel, $toPublishMessage, $incomingMessage, $deliveryOptions, $destinationExchange)
                {
                    $serializedMessage = $this->messageSerializer->serialize($toPublishMessage);
                    $messageHeaders = $this->prepareMessageHeaders($deliveryOptions);

                    $this->logPublishedMessage(
                        $toPublishMessage,
                        $destinationExchange,
                        $incomingMessage->routingKey,
                        $serializedMessage,
                        $messageHeaders
                    );

                    $channel
                        ->publish($serializedMessage, $messageHeaders, $destinationExchange, $incomingMessage->routingKey)
                        ->then(
                            null,
                            function(\Throwable $throwable)
                            {
                                $this->logger->critical(ThrowableFormatter::toString($throwable));
                            }
                        );
                }
            );
    }

    /**
     * Log message
     *
     * @param AbstractMessage $toPublishMessage
     * @param string          $destination
     * @param string          $routingKey
     * @param string          $serializeMessage
     * @param array           $headers
     *
     * @return void
     */
    private function logPublishedMessage(
        AbstractMessage $toPublishMessage,
        string $destination,
        string $routingKey,
        string $serializeMessage,
        array $headers = []
    ): void
    {
        if(true === $this->environment->isDebug())
        {
            $this->logger->debug(
                \sprintf(
                    '%s "%s" to "%s" exchange with routing key "%s". Message data: %s (with headers "%s")',
                    $toPublishMessage instanceof AbstractCommand
                        ? 'Send message'
                        : 'Publish event',
                    \get_class($toPublishMessage),
                    $destination,
                    $routingKey,
                    $serializeMessage,
                    \urldecode(\http_build_query($headers))
                )
            );
        }
    }

    /**
     * Collect message headers
     *
     * @param DeliveryOptions $deliveryOptions
     *
     * @return array
     */
    private function prepareMessageHeaders(DeliveryOptions $deliveryOptions): array
    {
        $messageHeaders = $deliveryOptions->getHeaders();

        $messageHeaders->set('fromHost', \gethostname());
        $messageHeaders->set('daemon', 'rabbitMQ');


        return $messageHeaders->all();
    }

    /**
     * The definition of the exchanger to which you want to send a message
     *
     * @param Message         $incomingMessage
     * @param DeliveryOptions $deliveryOptions
     *
     * @return string
     */
    private function locateDestinationExchange(Message $incomingMessage, DeliveryOptions $deliveryOptions): string
    {
        return '' !== $deliveryOptions->getDestination()
            ? $deliveryOptions->getDestination()
            : $incomingMessage->exchange;
    }
}
