<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use function Amp\call;
use Amp\Promise;
use ServiceBus\Common\Messages\Message;
use ServiceBus\Infrastructure\Retry\OperationRetryWrapper;
use ServiceBus\MessageSerializer\MessageEncoder;
use ServiceBus\MessageSerializer\Symfony\SymfonyMessageSerializer;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\Exceptions\SendMessageFailed;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Transport;

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
     * @param OperationRetryWrapper|null    $deliveryRetryHandler
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

        $options->headers[Transport::SERVICE_BUS_SERIALIZER_HEADER] = \get_class($this->encoder);

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
        return OutboundPackage::create(
            $payload,
            $options->headers,
            $destination,
            $options->traceId,
            $options->isPersistent,
            $options->isMandatory,
            $options->isImmediate,
            $options->expiredAfter
        );
    }
}
