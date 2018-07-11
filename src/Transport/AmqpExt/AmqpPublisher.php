<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\AmqpExt;

use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Transport\Destination;
use Desperado\ServiceBus\Transport\Encoder\MessageEncoder;
use Desperado\ServiceBus\Transport\Exceptions\MessageSendFailed;
use Desperado\ServiceBus\Transport\Exceptions\NotConfiguredTopic;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\OutboundEnvelope;

/**
 *
 */
final class AmqpPublisher implements Publisher
{
    /**
     * Configured amqp exchanges
     *
     * @var array<string, \AMQPExchange>
     */
    private $exchanges;

    /**
     * Message encoder
     *
     * @var MessageEncoder
     */
    private $encoder;

    /**
     * @param array<string, \AMQPExchange> $exchanges
     * @param MessageEncoder $encoder
     */
    public function __construct(array $exchanges, MessageEncoder $encoder)
    {
        $this->exchanges = $exchanges;
        $this->encoder   = $encoder;
    }

    /**
     * @inheritdoc
     */
    public function createEnvelope(Message $message, array $headers = []): OutboundEnvelope
    {
        return new AmqpOutboundEnvelope($this->encoder->encode($message), $headers);
    }

    /**
     * @inheritdoc
     */
    public function send(Destination $destination, OutboundEnvelope $envelope): void
    {
        /** @var AmqpOutboundEnvelope $envelope */

        $exchange = $this->extractExchange((string) $destination->topicName());

        try
        {
            $exchange->publish(
                $envelope->messageContent(),
                (string) $destination->routingKey(),
                true === $envelope->isMandatory() ? \AMQP_MANDATORY : \AMQP_NOPARAM,
                $envelope->attributes()
            );
        }
        catch(\Throwable $throwable)
        {
            throw new MessageSendFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param string $exchangeName
     *
     * @return \AMQPExchange
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\NotConfiguredTopic
     */
    private function extractExchange(string $exchangeName): \AMQPExchange
    {
        if(true === isset($this->exchanges[$exchangeName]))
        {
            return $this->exchanges[$exchangeName];
        }

        throw new NotConfiguredTopic(
            \sprintf('Topic "%s" was not configured. Please use createTopic method', $exchangeName)
        );
    }
}
