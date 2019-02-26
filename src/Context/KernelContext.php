<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Context;

use function Amp\call;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Endpoint\DefaultDeliveryOptions;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class KernelContext implements ServiceBusContext
{
    /**
     * @var IncomingPackage
     */
    private $incomingPackage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Outbound message routing
     *
     * @var EndpointRouter
     */
    private $endpointRouter;

    /**
     * Is the received message correct?
     *
     * @var bool
     */
    private $isValidMessage = true;

    /**
     * List of validate violations
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @psalm-var array<string, array<int, string>>
     * @var array
     */
    private $violations = [];

    /**
     * @param IncomingPackage $incomingPackage
     * @param EndpointRouter  $endpointRouter
     * @param LoggerInterface $logger
     */
    public function __construct(
        IncomingPackage $incomingPackage,
        EndpointRouter $endpointRouter,
        LoggerInterface $logger
    )
    {
        $this->incomingPackage = $incomingPackage;
        $this->endpointRouter  = $endpointRouter;
        $this->logger          = $logger;
    }

    /**
     * @inheritdoc
     */
    public function isValid(): bool
    {
        return $this->isValidMessage;
    }

    /**
     * @inheritdoc
     */
    public function violations(): array
    {
        return $this->violations;
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     *
     * @inheritdoc
     */
    public function delivery(object $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        $messageClass = \get_class($message);
        $endpoints    = $this->endpointRouter->route($messageClass);
        $logger       = $this->logger;

        $traceId = $this->incomingPackage->traceId();

        $options = $deliveryOptions ?? DefaultDeliveryOptions::create();

        if(null === $options->traceId())
        {
            $options->withTraceId($traceId);
        }

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(object $message, DeliveryOptions $options) use ($endpoints, $logger, $traceId): \Generator
            {
                foreach($endpoints as $endpoint)
                {
                    /** @var \ServiceBus\Endpoint\Endpoint $endpoint */

                    /** @noinspection DisconnectedForeachInstructionInspection */
                    $logger->debug(
                        'Send message "{messageClass}" to "{endpoint}"', [
                            'traceId'      => $traceId,
                            'messageClass' => \get_class($message),
                            'endpoint'     => $endpoint->name()
                        ]
                    );

                    yield $endpoint->delivery($message, $options);
                }
            },
            $message, $options
        );
    }

    /**
     * @inheritdoc
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
        $extra = \array_merge_recursive(
            $extra, [
                'traceId'   => $this->incomingPackage->traceId(),
                'packageId' => $this->incomingPackage->id()
            ]
        );

        $this->logger->log($level, $logMessage, $extra);
    }

    /**
     * @inheritdoc
     */
    public function logContextThrowable(\Throwable $throwable, string $level = LogLevel::ERROR, array $extra = []): void
    {
        $extra = \array_merge_recursive(
            $extra, ['throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())]
        );

        $this->logContextMessage($throwable->getMessage(), $extra, $level);
    }

    /**
     * @inheritdoc
     */
    public function operationId(): string
    {
        return $this->incomingPackage->id();
    }

    /**
     * @inheritdoc
     */
    public function traceId(): string
    {
        return (string) $this->incomingPackage->traceId();
    }

    /**
     * Message failed validation
     * Called by infrastructure components
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          MessageValidationExecutor
     *
     * @param array<string, array<int, string>> $violations
     *
     * @return void
     */
    private function validationFailed(array $violations): void
    {
        $this->isValidMessage = false;
        $this->violations     = $violations;
    }
}
