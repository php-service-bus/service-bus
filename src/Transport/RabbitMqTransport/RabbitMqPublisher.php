<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\RabbitMqTransport;

use Bunny\Channel;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\ThrowableFormatter;
use Desperado\ServiceBus\Transport\Message\Message;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

/**
 * RabbitMQ publisher
 */
final class RabbitMqPublisher
{
    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create publisher instance
     *
     * @param Environment     $environment
     * @param LoggerInterface $logger
     *
     * @return self
     */
    public static function create(Environment $environment, LoggerInterface $logger): self
    {
        $self = new self();

        $self->environment = $environment;
        $self->logger      = $logger;

        return $self;
    }

    /**
     * Publish message
     *
     * @param Channel $channel
     * @param Message $message
     * @param string  $exchangeType
     *
     * @return PromiseInterface
     */
    public function publish(Channel $channel, Message $message, string $exchangeType): PromiseInterface
    {
        return $channel
            ->exchangeDeclare($message->getExchange(), $exchangeType, true)
            ->then(
                function() use ($channel, $message)
                {
                    $headers = $message->getHeaders()->all();

                    if(false === isset($headers[RabbitMqConsumer::HEADER_DELIVERY_MODE_KEY]))
                    {
                        $headers[RabbitMqConsumer::HEADER_DELIVERY_MODE_KEY] = RabbitMqConsumer::PERSISTED_DELIVERY_MODE;
                    }

                    $channel
                        ->publish(
                            $message->getBody(),
                            $message->getHeaders()->all(),
                            (string) $message->getExchange(),
                            (string) $message->getRoutingKey()
                        )
                        ->then(
                            function() use ($message)
                            {
                                $this->logOutboundMessage($message);
                            }
                        );
                },
                function(\Throwable $throwable)
                {
                    $this->logger->critical(ThrowableFormatter::toString($throwable));
                }
            );
    }

    /**
     * Log outbound message in dev environment only
     *
     * @param Message $message
     *
     * @return void
     */
    private function logOutboundMessage(Message $message): void
    {
        if(true === $this->environment->isDebug())
        {
            $this->logger->debug(
                \sprintf(
                    'The message with the contents of "%s" was sent to the exchange "%s" with the routing key "%s" and headers "%s"',
                    $message->getBody(),
                    $message->getExchange(),
                    $message->getRoutingKey(),
                    \urldecode(\http_build_query($message->getHeaders()->all()))
                )
            );
        }
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
