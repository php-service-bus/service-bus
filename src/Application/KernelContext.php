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

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\SendOptions;
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
     * @param callable        $messagePublisher  function(Message $message, array $headers, IncomingEnvelope
     *                                           $incomingEnvelope): Promise {}
     * @param LoggerInterface $logger
     */
    public function __construct(IncomingPackage $incomingPackage, LoggerInterface $logger)
    {
        $this->incomingPackage = $incomingPackage;
        $this->logger          = $logger;
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
    public function delivery(Message ...$messages): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function send(Command $command, SendOptions $options): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function publish(Event $event, SendOptions $options): Promise
    {
        return new Success();
    }

    /**
     * @inheritdoc
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
        $extra = \array_merge_recursive($extra, ['operationId' => $this->incomingPackage->id()]);

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
     * Message failed validation
     * Called by infrastructure components
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          MessageValidationExecutor
     *
     * @param array $violations
     *
     * @return void
     */
    private function validationFailed(array $violations): void
    {
        $this->isValidMessage = false;
        $this->violations     = $violations;
    }
}
