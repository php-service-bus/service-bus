<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Context;

use function Amp\call;
use function ServiceBus\Common\collectThrowableDetails;
use Amp\Delayed;
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
     * The package containing the incoming message.
     *
     * @var IncomingPackage
     */
    private $incomingPackage;

    /**
     * Incoming message object.
     *
     * @var object
     */
    private $receivedMessage;

    /**
     * Outbound message routing.
     *
     * @var EndpointRouter
     */
    private $endpointRouter;

    /**
     * Is the received message correct?
     *
     * Note: This value is stamped from the infrastructure level
     *
     * @see MessageValidationExecutor::134
     *
     * @var bool
     */
    private $isValidMessage = true;

    /**
     * List of validate violations.
     *
     * Note: This value is stamped from the infrastructure level
     *
     * @see       MessageValidationExecutor::134
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @psalm-var array<string, array<int, string>>
     *
     * @var array
     */
    private $violations = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IncomingPackage $incomingPackage
     * @param object          $receivedMessage
     * @param EndpointRouter  $endpointRouter
     * @param LoggerInterface $logger
     */
    public function __construct(
        IncomingPackage $incomingPackage,
        object $receivedMessage,
        EndpointRouter $endpointRouter,
        LoggerInterface $logger
    ) {
        $this->incomingPackage = $incomingPackage;
        $this->receivedMessage = $receivedMessage;
        $this->endpointRouter  = $endpointRouter;
        $this->logger          = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return $this->isValidMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function violations(): array
    {
        return $this->violations;
    }

    /**
     * {@inheritdoc}
     */
    public function delivery(object $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        $messageClass = \get_class($message);
        $endpoints    = $this->endpointRouter->route($messageClass);
        $logger       = $this->logger;

        $traceId = $this->incomingPackage->traceId();

        $options = $deliveryOptions ?? DefaultDeliveryOptions::create();

        if (null === $options->traceId())
        {
            $options->withTraceId($traceId);
        }

        return call(
            static function(object $message, DeliveryOptions $options) use ($endpoints, $logger, $traceId): void
            {
                foreach ($endpoints as $endpoint)
                {
                    /** @var \ServiceBus\Endpoint\Endpoint $endpoint */

                    /** @noinspection DisconnectedForeachInstructionInspection */
                    $logger->debug(
                        'Send message "{messageClass}" to "{endpoint}"',
                        [
                            'traceId'      => $traceId,
                            'messageClass' => \get_class($message),
                            'endpoint'     => $endpoint->name(),
                        ]
                    );

                    $endpoint->delivery($message, $options)->onResolve(
                        static function(?\Throwable $throwable): void
                        {
                            if (null !== $throwable)
                            {
                                throw  $throwable;
                            }
                        }
                    );
                }
            },
            $message,
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function return(int $secondsDelay = 3): Promise
    {
        return call(
            function(int $delay): \Generator
            {
                yield (new Delayed($delay));

                yield $this->delivery($this->receivedMessage);
            },
            0 < $secondsDelay ? $secondsDelay * 1000 : 1000
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
        $extra = \array_merge_recursive(
            $extra,
            [
                'traceId'   => $this->incomingPackage->traceId(),
                'packageId' => $this->incomingPackage->id(),
            ]
        );

        $this->logger->log($level, $logMessage, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function logContextThrowable(\Throwable $throwable, array $extra = [], string $level = LogLevel::ERROR): void
    {
        $extra = \array_merge_recursive(
            $extra,
            collectThrowableDetails($throwable)
        );

        $this->logContextMessage($throwable->getMessage(), $extra, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function operationId(): string
    {
        return $this->incomingPackage->id();
    }

    /**
     * {@inheritdoc}
     */
    public function traceId(): string
    {
        return (string) $this->incomingPackage->traceId();
    }

    /**
     * {@inheritdoc}
     */
    public function headers(): array
    {
        return $this->incomingPackage->headers();
    }

    /**
     * Message failed validation
     * Called by infrastructure components.
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
