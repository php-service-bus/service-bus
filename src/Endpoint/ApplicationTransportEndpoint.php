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
    public function defaultDestination(): TransportLevelDestination
    {
        return $this->destination;
    }

    /**
     * @inheritDoc
     */
    public function delivery(Message $message, DeliveryOptions $options): Promise
    {
        $deferred = new Deferred();

        $transport = $this->transport;
        $encoder   = $this->encoder;

        $watcherId = Loop::defer(
            static function() use (&$watcherId, $deferred, $message, $options, $transport, $encoder): \Generator
            {
                try
                {
                    $encoded = $encoder->encode($message);
                    $package = self::createPackage($encoded, $options);

                    yield $transport->send($package);

                    $deferred->resolve();
                }
                catch(\Throwable $throwable)
                {
                    $deferred->resolve($throwable);
                }
                finally
                {
                    Loop::cancel($watcherId);
                }
            }
        );

        Loop::unreference($watcherId);

        return $deferred->promise();
    }

    /**
     * Create outbound package with specified parameters
     *
     * @param string          $payload
     * @param DeliveryOptions $options
     *
     * @return OutboundPackage
     */
    private static function createPackage(string $payload, DeliveryOptions $options): OutboundPackage
    {
        $package = new OutboundPackage(
            new InMemoryStream($payload),
            $options->headers(),
            $options->recipient()->transportDestination()
        );

        $package->setExpiredAfter($options->expiredAfter());
        $package->setIsImmediate($options->isImmediate());
        $package->setIsMandatory($options->isMandatory());
        $package->setIsPersistent($options->isPersistent());

        return $package;
    }
}
