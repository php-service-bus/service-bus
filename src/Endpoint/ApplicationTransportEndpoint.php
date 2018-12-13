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

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;
use Desperado\ServiceBus\Infrastructure\Retry\OperationRetryWrapper;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\SendMessageFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;

/**
 * Application level transport endpoint
 */
final class ApplicationTransportEndpoint implements Endpoint
{
    public const  ENDPOINT_NAME = 'application';

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
     * A wrapper on an operation that performs repetitions in case of an error
     *
     * @var OperationRetryWrapper
     */
    private $deliveryRetryHandler;

    /**
     * @param Transport                     $transport
     * @param AmqpTransportLevelDestination $destination
     * @param MessageEncoder|null           $encoder
     */
    public function __construct(
        Transport $transport,
        AmqpTransportLevelDestination $destination,
        ?MessageEncoder $encoder = null,
        ?OperationRetryWrapper $deliveryRetryHandler = null
    )
    {
        $this->transport            = $transport;
        $this->destination          = $destination;
        $this->encoder              = $encoder ?? new SymfonyMessageSerializer();
        $this->deliveryRetryHandler = $deliveryRetryHandler ?? new OperationRetryWrapper();
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
        $transport = $this->transport;

        $encoded = $this->encoder->encode($message);

        $options->headers[Transport::SERVICE_BUS_SERIALIZER_HEADER] = $this->encoder->name();

        $package = self::createPackage($encoded, $options, $this->destination);

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call($this->deliveryRetryHandler,
            static function() use ($transport, $package): \Generator
            {
                yield $transport->send($package);
            },
            SendMessageFailed::class
        );
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
        $package = new OutboundPackage($payload, $options->headers, $destination);

        $package->expiredAfter   = $options->expiredAfter;
        $package->immediateFlag  = $options->isImmediate;
        $package->mandatoryFlag  = $options->isMandatory;
        $package->persistentFlag = $options->isPersistent;
        $package->traceId        = $options->traceId;

        return $package;
    }
}
