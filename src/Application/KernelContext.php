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

namespace Desperado\ServiceBus\Application;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\Endpoint\DeliveryOptions;
use Desperado\ServiceBus\Endpoint\EndpointRouter;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 *
 */
final class KernelContext implements MessageDeliveryContext, LoggingInContext
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
     * @var array<string, array<int, string>>
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
        $this->endpointRouter = $endpointRouter;
        $this->logger = $logger;
    }

    /**
     * Is the received message correct?
     * If validation is not enabled in the handler parameters, it always returns true
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValidMessage;
    }

    /**
     * If the message is incorrect, returns a list of violations
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @return array<string, array<int, string>>
     */
    public function violations(): array
    {
        return $this->violations;
    }

    /**
     * @inheritdoc
     */
    public function delivery(Message $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        $messageClass = \get_class($message);
        $endpoints = $this->endpointRouter->route($messageClass);
        $logger = $this->logger;

        $traceId = $this->incomingPackage->traceId();

        $options = $deliveryOptions ?? new DeliveryOptions();

        if(null === $options->traceId)
        {
            $options->traceId = $traceId;
        }

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Message $message, DeliveryOptions $options) use ($endpoints, $logger, $traceId): \Generator
            {
                foreach($endpoints as $endpoint)
                {
                    /** @var \Desperado\ServiceBus\Endpoint\Endpoint $endpoint */

                    /** @noinspection DisconnectedForeachInstructionInspection */
                    $logger->debug(
                        'Send message "{messageClass}" to "{entryPoint}" entry point', [
                            'traceId'      => $traceId,
                            'messageClass' => \get_class($message),
                            'entryPoint'   => $endpoint->name()
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
     * Receive incoming operation id
     *
     * @return string
     */
    public function operationId(): string
    {
        return $this->incomingPackage->id();
    }

    /**
     * Receive trace message id
     *
     * @return string
     */
    public function traceId(): string
    {
        return $this->incomingPackage->traceId();
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
        $this->violations = $violations;
    }
}
