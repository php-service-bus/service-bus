<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Endpoint;

use ServiceBus\Common\Metadata\ServiceBusMetadata;
use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
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
    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var DeliveryDestination
     */
    private $destination;

    /**
     * @var EndpointEncoder
     */
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
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $name;

    /**
     * @psalm-param non-empty-string $name
     */
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

    public function name(): string
    {
        return $this->name;
    }

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

    public function delivery(DeliveryPackage $package): Promise
    {
        return $this->deferredDelivery(
            $this->createOutboundPackage(
                package: $package,
                destination: $this->destination
            )
        );
    }

    public function deliveryBulk(array $packages): Promise
    {
        $outboundPackages = \array_map(
            function (DeliveryPackage $package): OutboundPackage
            {
                return $this->createOutboundPackage(
                    package: $package,
                    destination: $this->destination
                );
            },
            $packages
        );

        return $this->deferredDelivery(
            ...$outboundPackages
        );
    }

    /**
     * Create outbound package with specified parameters.
     */
    private function createOutboundPackage(
        DeliveryPackage $package,
        DeliveryDestination $destination
    ): OutboundPackage {
        $payload = $this->encoder->handler->encode($package->message);

        /** @psalm-var array<string, int|float|string|null> $headers */
        $headers = \array_merge(
            $package->options->headers(),
            $package->metadata->variables(),
            [
                ServiceBusMetadata::SERVICE_BUS_MESSAGE_TYPE    => \get_class($package->message),
                ServiceBusMetadata::SERVICE_BUS_SERIALIZER_TYPE => $this->encoder->tag,
                'content-type' => $this->encoder->handler->contentType()
            ]
        );

        return new OutboundPackage(
            traceId: $package->metadata->traceId(),
            payload: $payload,
            headers: $headers,
            destination: $destination,
            persist: $package->options->isPersistent(),
            mandatory: false,
            immediate: $package->options->isHighestPriority(),
            expiredAfter: $package->options->expirationAfter()
        );
    }

    private function deferredDelivery(OutboundPackage ...$packages): Promise
    {
        $deferred = new Deferred();

        Loop::defer(
            function () use ($packages, $deferred): void
            {
                $promise = call(
                    $this->deliveryRetryHandler,
                    function () use ($packages): \Generator
                    {
                        yield $this->transport->send(...$packages);
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
