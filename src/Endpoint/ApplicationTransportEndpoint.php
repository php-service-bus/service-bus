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

namespace Desperado\ServiceBus\Endpoint;

use Amp\ByteStream\InMemoryStream;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;

/**
 * Application level transport endpoint
 */
final class ApplicationTransportEndpoint implements Endpoint
{
    public const ENDPOINT_NAME = 'application';

    /**
     * @var Transport
     */
    private $transport;

    /**
     * Which exchange (and with which key) the message will be sent to
     *
     * @var AmqpTransportLevelDestination
     */
    private $destination;

    /**
     * Convert message to string
     *
     * @var MessageEncoder
     */
    private $encoder;

    /**
     * @param Transport                     $transport
     * @param AmqpTransportLevelDestination $destination
     * @param MessageEncoder|null           $encoder
     */
    public function __construct(
        Transport $transport,
        AmqpTransportLevelDestination $destination,
        ?MessageEncoder $encoder = null
    )
    {
        $this->transport   = $transport;
        $this->destination = $destination;
        $this->encoder     = $encoder ?? new SymfonyMessageSerializer();
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return self::ENDPOINT_NAME;
    }

    /**
     * @inheritDoc
     */
    public function delivery(Message $message, DeliveryOptions $options): Promise
    {
        $deferred = new Deferred();

        $transport = $this->transport;
        $encoder   = $this->encoder;

        $destination = $this->destination;

         Loop::defer(
            static function() use ($deferred, $message, $options, $transport, $encoder, $destination): \Generator
            {
                try
                {
                    $encoded = $encoder->encode($message);
                    $package = self::createPackage($encoded, $options, $destination);

                    yield $transport->send($package);

                    $deferred->resolve();

                    unset($encoded, $package);
                }
                catch(\Throwable $throwable)
                {
                    $deferred->resolve($throwable);
                }
            }
        );

        return $deferred->promise();
    }

    /**
     * Create outbound package with specified parameters
     *
     * @param string                        $payload
     * @param DeliveryOptions               $options
     * @param AmqpTransportLevelDestination $destination
     *
     * @return OutboundPackage
     */
    private static function createPackage(
        string $payload,
        DeliveryOptions $options,
        AmqpTransportLevelDestination $destination
    ): OutboundPackage
    {
        $package = new OutboundPackage(
            new InMemoryStream($payload),
            $options->headers(),
            $destination
        );

        $package->setExpiredAfter($options->expiredAfter());
        $package->setIsImmediate($options->isImmediate());
        $package->setIsMandatory($options->isMandatory());
        $package->setIsPersistent($options->isPersistent());

        return $package;
    }
}
