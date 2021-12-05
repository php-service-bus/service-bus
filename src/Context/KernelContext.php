<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Context;

use Amp\Promise;
use ServiceBus\Common\Context\ContextLogger;
use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\Context\OutcomeMessageMetadata;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Context\ValidationViolations;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Common\Metadata\ServiceBusMetadata;
use ServiceBus\Endpoint\DeliveryPackage;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Endpoint\Options\DeliveryOptionsFactory;
use function Amp\call;

final class KernelContext implements ServiceBusContext
{
    /**
     * @psalm-var array<non-empty-string, int|float|string|null>
     *
     * @var array
     */
    private $headers;

    /**
     * @var IncomingMessageMetadata
     */
    private $metadata;

    /**
     * @var EndpointRouter
     */
    private $endpointRouter;

    /**
     * @var DeliveryOptionsFactory
     */
    private $optionsFactory;

    /**
     * @var ContextLogger
     */
    private $logger;

    /**
     * @var ValidationViolations|null
     */
    private $validationViolations;

    /**
     * @psalm-param array<non-empty-string, int|float|string|null> $headers
     */
    public function __construct(
        array $headers,
        IncomingMessageMetadata $metadata,
        EndpointRouter $endpointRouter,
        DeliveryOptionsFactory $optionsFactory,
        ContextLogger $logger
    ) {
        $this->headers        = $headers;
        $this->metadata       = $metadata;
        $this->endpointRouter = $endpointRouter;
        $this->optionsFactory = $optionsFactory;
        $this->logger         = $logger;
    }

    public function delivery(
        object $message,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return call(
            function () use ($message, $deliveryOptions, $withMetadata): \Generator
            {
                /** @psalm-var class-string $messageClass */
                $messageClass = \get_class($message);

                $endpoints       = $this->endpointRouter->route($messageClass);
                $deliveryOptions = $deliveryOptions ?? $this->optionsFactory->create($messageClass);
                $metadata        = $this->enrichOutcomeMessageMetadata(
                    metadata: $withMetadata ?? DeliveryMessageMetadata::create($this->metadata->traceId()),
                    isRetry: false
                );

                $promises = [];

                foreach ($endpoints as $endpoint)
                {
                    $this->logger()->debug(
                        'Send message "{outcomeMessage}" to "{endpoint}"',
                        [
                            'outcomeMessage' => \get_class($message),
                            'endpoint'       => $endpoint->name(),
                        ]
                    );

                    $promises[] = $endpoint->delivery(
                        new DeliveryPackage(
                            message: $message,
                            options: $deliveryOptions,
                            metadata: $metadata
                        )
                    );
                }

                if (\count($promises) !== 0)
                {
                    yield $promises;
                }
            }
        );
    }

    public function deliveryBulk(
        array $messages,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return call(
            function () use ($messages, $deliveryOptions, $withMetadata): \Generator
            {
                $metadata = $this->enrichOutcomeMessageMetadata(
                    metadata: $withMetadata ?? DeliveryMessageMetadata::create($this->metadata->traceId()),
                    isRetry: false
                );

                $deliveryQueue = [];

                foreach ($messages as $message)
                {
                    /** @psalm-var class-string $messageClass */
                    $messageClass    = \get_class($message);
                    $deliveryOptions = $deliveryOptions ?? $this->optionsFactory->create($messageClass);
                    $endpoints       = $this->endpointRouter->route($messageClass);

                    foreach ($endpoints as $endpoint)
                    {
                        $deliveryQueue[$endpoint->name()][] = new DeliveryPackage(
                            message: $message,
                            options: $deliveryOptions,
                            metadata: $metadata
                        );
                    }
                }

                foreach ($deliveryQueue as $endpointIndex => $packages)
                {
                    $endpoint = $this->endpointRouter->endpoint($endpointIndex);

                    $this->logger()->debug(
                        'Send messages "{outcomeMessages}" to "{endpoint}"',
                        [
                            'endpoint'        => $endpoint->name(),
                            'outcomeMessages' => \implode(',', \array_map(
                                static function (DeliveryPackage $package): string
                                {
                                    return \get_class($package->message);
                                },
                                $packages
                            )),
                        ]
                    );

                    yield $endpoint->deliveryBulk($packages);
                }
            }
        );
    }

    public function violations(): ?ValidationViolations
    {
        return $this->validationViolations;
    }

    public function logger(): ContextLogger
    {
        return $this->logger;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function metadata(): IncomingMessageMetadata
    {
        return $this->metadata;
    }

    private function enrichOutcomeMessageMetadata(OutcomeMessageMetadata $metadata, bool $isRetry): OutcomeMessageMetadata
    {
        if ($isRetry)
        {
            $metadata = $metadata->with(
                key: ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT,
                value: ((int) $this->metadata->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT, 0)) + 1
            );
        }

        return $metadata;
    }

    /**
     * Message failed validation
     * Called by infrastructure components.
     *
     * @codeCoverageIgnore
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          MessageValidationExecutor
     */
    private function validationFailed(ValidationViolations $validationViolations): void
    {
        $this->validationViolations = $validationViolations;
    }
}
