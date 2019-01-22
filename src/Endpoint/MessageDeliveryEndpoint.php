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
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Exceptions\SendMessageFailed;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Transport;

/**
 * Application level transport endpoint
 */
final class MessageDeliveryEndpoint implements Endpoint
{
    /**
     * @var Transport
     */
    private $transport;

    /**
     * Which exchange (and with which key) the message will be sent to
     *
     * @var DeliveryDestination
     */
    private $destination;

    /**
     * Convert message to string
     *
     * @var EndpointEncoder
     */
    private $encoder;

    /**
     * A wrapper on an operation that performs repetitions in case of an error
     *
     * @var OperationRetryWrapper
     */
    private $deliveryRetryHandler;

    /**
     * Endpoint name
     *
     * @var string
     */
    private $name;

    /**
     * @param string                     $name
     * @param Transport                  $transport
     * @param DeliveryDestination        $destination
     * @param EndpointEncoder|null       $encoder
     * @param OperationRetryWrapper|null $deliveryRetryHandler
     */
    public function __construct(
        string $name,
        Transport $transport,
        DeliveryDestination $destination,
        ?EndpointEncoder $encoder = null,
        ?OperationRetryWrapper $deliveryRetryHandler = null
    )
    {
        $this->name                 = $name;
        $this->transport            = $transport;
        $this->destination          = $destination;
        $this->encoder              = $encoder ?? EndpointEncoder::createDefault();
        $this->deliveryRetryHandler = $deliveryRetryHandler ?? new OperationRetryWrapper();
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function delivery(Message $message, DefaultDeliveryOptions $options): Promise
    {
        $encoded = $this->encoder->handler->encode($message);

        $options= $options->withHeader(Transport::SERVICE_BUS_SERIALIZER_HEADER, $this->encoder->tag);

        $package = self::createPackage($encoded, $options, $this->destination);

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call($this->deliveryRetryHandler,
            function() use ($package): \Generator
            {
                yield $this->transport->send($package);
            },
            SendMessageFailed::class
        );
    }

    /**
     * Create outbound package with specified parameters
     *
     * @param string                 $payload
     * @param DefaultDeliveryOptions $options
     * @param DeliveryDestination    $destination
     *
     * @return OutboundPackage
     */
    private static function createPackage(
        string $payload,
        DefaultDeliveryOptions $options,
        DeliveryDestination $destination
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
