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
     * Endpoint name
     *
     * @var string
     */
    private $name;

    /**
     * @param string                     $name
     * @param Transport                  $transport
     * @param DeliveryDestination        $destination
     * @param MessageEncoder|null        $encoder
     * @param OperationRetryWrapper|null $deliveryRetryHandler
     */
    public function __construct(
        string $name,
        Transport $transport,
        DeliveryDestination $destination,
        ?MessageEncoder $encoder = null,
        ?OperationRetryWrapper $deliveryRetryHandler = null
    )
    {
        $this->name                 = $name;
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
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function delivery(Message $message, DeliveryOptions $options): Promise
    {
        $encoded = $this->encoder->encode($message);

        /** @noinspection GetClassUsageInspection Encoder cant't be null */
        $options->headers[Transport::SERVICE_BUS_SERIALIZER_HEADER] = \get_class($this->encoder);

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
     * @param string              $payload
     * @param DeliveryOptions     $options
     * @param DeliveryDestination $destination
     *
     * @return OutboundPackage
     */
    private static function createPackage(
        string $payload,
        DeliveryOptions $options,
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
