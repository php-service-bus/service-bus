<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Context;

use Amp\Delayed;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Endpoint\Options\DeliveryOptionsFactory;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\call;
use function ServiceBus\Common\collectThrowableDetails;

/**
 *
 */
final class KernelContext implements ServiceBusContext
{
    /** @var IncomingPackage */
    private $incomingPackage;

    /** @var object */
    private $receivedMessage;

    /** @var EndpointRouter */
    private $endpointRouter;

    /** @var DeliveryOptionsFactory */
    private $optionsFactory;

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

    public function __construct(
        IncomingPackage $incomingPackage,
        object $receivedMessage,
        EndpointRouter $endpointRouter,
        DeliveryOptionsFactory $optionsFactory,
        LoggerInterface $logger
    ) {
        $this->incomingPackage = $incomingPackage;
        $this->receivedMessage = $receivedMessage;
        $this->endpointRouter  = $endpointRouter;
        $this->optionsFactory  = $optionsFactory;
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
        return call(
            function () use ($message, $deliveryOptions): \Generator
            {
                /** @psalm-var class-string $messageClass */
                $messageClass = \get_class($message);

                $traceId = $this->incomingPackage->traceId();
                $options = $deliveryOptions ?? $this->optionsFactory->create($traceId, $messageClass);

                if ($options->traceId() === null)
                {
                    $options->withTraceId($traceId);
                }

                $endpoints = $this->endpointRouter->route($messageClass);

                $promises = [];

                foreach ($endpoints as $endpoint)
                {
                    $this->logger->debug(
                        'Send message "{messageClass}" to "{endpoint}"',
                        [
                            'traceId'      => $options->traceId(),
                            'messageClass' => \get_class($message),
                            'endpoint'     => $endpoint->name(),
                        ]
                    );

                    $promises[] = $endpoint->delivery($message, $options);
                }

                if (\count($promises) !== 0)
                {
                    yield $promises;
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function return(int $secondsDelay = 3): Promise
    {
        return call(
            function (int $delay): \Generator
            {
                yield new Delayed($delay);

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
        $extra['traceId']   = $this->incomingPackage->traceId();
        $extra['packageId'] = $this->incomingPackage->id();

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
     * @codeCoverageIgnore
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          MessageValidationExecutor
     *
     * @psalm-param  array<string, array<int, string>> $violations
     */
    private function validationFailed(array $violations): void
    {
        $this->isValidMessage = false;
        $this->violations     = $violations;
    }
}
