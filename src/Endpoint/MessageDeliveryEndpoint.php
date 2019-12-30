<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Infrastructure\Retry\OperationRetryWrapper;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Exceptions\SendMessageFailed;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Transport;

/**
 * Application level transport endpoint.
 */
final class MessageDeliveryEndpoint implements Endpoint
{
    /** @var Transport */
    private $transport;

    /** @var DeliveryDestination */
    private $destination;

    /** @var EndpointEncoder */
    private $encoder;

    /**
     * A wrapper on an operation that performs repetitions in case of an error.
     *
     * @var OperationRetryWrapper
     */
    private $deliveryRetryHandler;

    /**
     * Endpoint name.
     *
     * @var string
     */
    private $name;

    public function __construct(
        string $name,
        Transport $transport,
        DeliveryDestination $destination,
        ?EndpointEncoder $encoder = null,
        ?OperationRetryWrapper $deliveryRetryHandler = null
    ) {
        $this->name                 = $name;
        $this->transport            = $transport;
        $this->destination          = $destination;
        $this->encoder              = $encoder ?? EndpointEncoder::createDefault();
        $this->deliveryRetryHandler = $deliveryRetryHandler ?? new OperationRetryWrapper();
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function withNewDeliveryDestination(DeliveryDestination $destination): Endpoint
    {
        return new self(
            $this->name,
            $this->transport,
            $destination,
            $this->encoder,
            $this->deliveryRetryHandler
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delivery(object $message, DeliveryOptions $options): Promise
    {
        $encoded = $this->encoder->handler->encode($message);

        $options->withHeader(Transport::SERVICE_BUS_SERIALIZER_HEADER, $this->encoder->tag);

        return $this->deferredDelivery(
            self::createPackage($encoded, $options, $this->destination)
        );
    }

    /**
     * Create outbound package with specified parameters.
     */
    private static function createPackage(
        string $payload,
        DeliveryOptions $options,
        DeliveryDestination $destination
    ): OutboundPackage {
        return new OutboundPackage(
            $payload,
            $options->headers(),
            $destination,
            $options->traceId(),
            $options->isPersistent(),
            // @todo: fixme
            false,
            false,
            $options->expirationAfter()
        );
    }

    private function deferredDelivery(OutboundPackage $package): Promise
    {
        $deferred = new Deferred();

        Loop::defer(
            function () use ($package, $deferred): void
            {
                $promise = call(
                    $this->deliveryRetryHandler,
                    function () use ($package): \Generator
                    {
                        yield $this->transport->send($package);
                    },
                    SendMessageFailed::class
                );

                $promise->onResolve(
                    static function (?\Throwable $throwable) use ($deferred): void
                    {
                        if ($throwable === null)
                        {
                            $deferred->resolve();

                            return;
                        }

                        $deferred->fail($throwable);
                    }
                );
            }
        );

        return $deferred->promise();
    }
}
