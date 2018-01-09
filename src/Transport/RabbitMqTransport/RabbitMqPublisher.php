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
use Desperado\Domain\Transport\Message\Message;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

/**
 * RabbitMQ publisher
 */
class RabbitMqPublisher
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
     * @return RabbitMqPublisher
     */
    public static function create(Environment $environment, LoggerInterface $logger)
    {
        $self = new self();

        $self->environment = $environment;
        $self->logger = $logger;

        return $self;
    }

    /**
     * Publish message
     *
     * @param Channel $channel
     * @param Message $message
     *
     * @return PromiseInterface
     */
    public function publish(Channel $channel, Message $message): PromiseInterface
    {
        return $channel
            ->exchangeDeclare($message->getExchange(), 'direct', true)
            ->then(
                function() use ($channel, $message)
                {
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
                                if(true === $this->environment->isDebug())
                                {
                                    $this->logger->debug(
                                        \sprintf(
                                            'The message with the contents of "%s" was sent to the exchange "%s" with the routing key "%s"',
                                            $message->getBody(),
                                            $message->getExchange(),
                                            $message->getRoutingKey()
                                        )
                                    );
                                }
                            },
                            function(\Throwable $throwable)
                            {
                                $this->logger->critical(ThrowableFormatter::toString($throwable));
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
     * Close constructor
     */
    private function __construct()
    {

    }
}
