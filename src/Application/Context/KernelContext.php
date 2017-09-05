<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application\Context;

use Desperado\Framework\Common\Formatter\ThrowableFormatter;
use Desperado\Framework\Domain\Environment\Environment;
use Desperado\Framework\Domain\Messages\CommandInterface;
use Desperado\Framework\Domain\Messages\EventInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\Framework\Infrastructure\CQRS\Context\MessageExecutionOptionsContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\Options;
use Desperado\Framework\Infrastructure\EventSourcing\Saga\AbstractSaga;
use Desperado\Framework\Infrastructure\StorageManager\SagaStorageManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Kernel context
 */
class KernelContext implements DeliveryContextInterface, MessageExecutionOptionsContextInterface
{
    /**
     * Context messages data
     *
     * @var Variables\ContextMessages
     */
    private $contextMessages;

    /**
     * Context storage data
     *
     * @var Variables\ContextStorage
     */
    private $contextStorage;

    /**
     * Logger context
     *
     * @var Variables\ContextLogger
     */
    private $contextLogger;

    /**
     * Context entry point
     *
     * @var Variables\ContextEntryPoint
     */
    private $contextEntryPoint;

    /**
     * Command execution options
     *
     * @var Options\CommandOptions
     */
    private $commandExecutionOptions;

    /**
     * Event execution options
     *
     * @var Options\EventOptions
     */
    private $eventExecutionOptions;

    /**
     * @param Variables\ContextEntryPoint $contextEntryPoint
     * @param Variables\ContextMessages   $contextMessages
     * @param Variables\ContextStorage    $contextStorage
     * @param Variables\ContextLogger     $contextLogger
     */
    public function __construct(
        Variables\ContextEntryPoint $contextEntryPoint,
        Variables\ContextMessages $contextMessages,
        Variables\ContextStorage $contextStorage,
        Variables\ContextLogger $contextLogger
    )
    {
        $this->contextEntryPoint = $contextEntryPoint;
        $this->contextMessages = $contextMessages;
        $this->contextStorage = $contextStorage;
        $this->contextLogger = $contextLogger;
    }

    /**
     * Log Throwable
     *
     * @param \Throwable $throwable
     * @param int        $level
     *
     * @return void
     */
    public function logThrowable(\Throwable $throwable, int $level = Logger::ERROR): void
    {
        $this->getLogger()->log($level, ThrowableFormatter::toString($throwable));
    }

    /**
     * Get error logger callable
     *
     * @param int $level
     *
     * @return callable  function(\Throwable $throwable) {}
     */
    public function getLogThrowableCallable(int $level = Logger::ERROR): callable
    {
        $logger = $this->getLogger();

        return function(\Throwable $throwable) use ($level, $logger)
        {
            $logger->log($level, ThrowableFormatter::toString($throwable));
        };
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int    $level
     *
     * @return void
     */
    public function logMessage(string $message, int $level = Logger::DEBUG): void
    {
        $this->getLogger()->log($level, $message);
    }

    /**
     * Get manage for specified saga
     *
     * @param string|AbstractSaga $objectOrNamespace
     *
     * @return SagaStorageManagerInterface
     */
    public function getSagaStorageManager($objectOrNamespace): SagaStorageManagerInterface
    {
        $objectOrNamespace = true === \is_object($objectOrNamespace)
            ? \get_class($objectOrNamespace)
            : $objectOrNamespace;

        return $this->contextStorage->getSagaStorageManager($objectOrNamespace);
    }

    /**
     * @inheritdoc
     */
    public function appendCommandExecutionOptions(Options\CommandOptions $options): void
    {
        $this->commandExecutionOptions = $options;
    }

    /**
     * @inheritdoc
     */
    public function appendEventExecutionOptions(Options\EventOptions $options): void
    {
        $this->eventExecutionOptions = $options;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(MessageInterface $message): Options\MessageOptionsInterface
    {
        return $message instanceof EventInterface
            ? $this->eventExecutionOptions
            : $this->commandExecutionOptions;
    }

    /**
     * Get logger
     *
     * @param string|null $channelName
     *
     * @return LoggerInterface
     */
    public function getLogger(string $channelName = null): LoggerInterface
    {
        return $this->contextLogger->getLogger($channelName);
    }

    /**
     * Get application environment
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->contextEntryPoint->getEnvironment();
    }

    /**
     * Send/publish message
     *
     * @param MessageInterface     $message
     * @param DeliveryOptions|null $deliveryOptions
     *
     * @return void
     */
    public function delivery(MessageInterface $message, DeliveryOptions $deliveryOptions = null)
    {
        $deliveryOptions = $deliveryOptions ?? new DeliveryOptions();

        $message instanceof EventInterface
            ? $this->publish($message, $deliveryOptions)
            /** @var CommandInterface $message */
            : $this->send($message, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->contextMessages->deliveryMessage($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->contextMessages->deliveryMessage($event, $deliveryOptions);
    }
}
