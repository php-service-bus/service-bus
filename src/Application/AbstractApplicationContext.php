<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application;

use Desperado\CQRS\Context\ContextLoggerInterface;
use Desperado\CQRS\Context\DeliveryContextInterface;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\CQRS\Context\ExecutionOptionsContextInterface;
use Desperado\CQRS\ExecutionContextOptions\CommandHandlerOptions;
use Desperado\CQRS\ExecutionContextOptions\EventListenerOptions;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ThrowableFormatter;
use Desperado\EventSourcing\Service\EventSourcingService;
use Desperado\Saga\Service\SagaService;
use Psr\Log\LogLevel;

/**
 * Base application context
 */
abstract class AbstractApplicationContext
    implements DeliveryContextInterface, ExecutionOptionsContextInterface, ContextLoggerInterface
{
    /**
     * Origin context
     *
     * @var DeliveryContextInterface
     */
    private $originContext;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Execute command options
     *
     * @var CommandHandlerOptions
     */
    private $commandExecutionOptions;

    /**
     * Event execution options
     *
     * @var EventListenerOptions
     */
    private $eventListenerOptions;

    /**
     * Event sourcing service
     *
     * @var EventSourcingService
     */
    private $eventSourcingService;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * @param DeliveryContextInterface $originContext
     * @param string                   $entryPointName
     * @param EventSourcingService     $eventSourcingService
     * @param SagaService              $sagaService
     */
    public function __construct(
        DeliveryContextInterface $originContext,
        string $entryPointName,
        EventSourcingService $eventSourcingService,
        SagaService $sagaService
    )
    {
        $this->originContext = $originContext;
        $this->entryPointName = $entryPointName;
        $this->eventSourcingService = $eventSourcingService;
        $this->sagaService = $sagaService;
    }

    /**
     * @inheritdoc
     */
    public function logContextMessage(
        AbstractMessage $message,
        string $logMessage,
        string $level = LogLevel::INFO,
        array $extra = []
    ): void
    {
        $options = $message instanceof AbstractCommand
            ? $this->commandExecutionOptions
            : $this->eventListenerOptions;

        $messageChannel = null !== $options
            ? $options->getLoggerChannel()
            : '';

        ApplicationLogger::log(
            $messageChannel,
            $logMessage,
            $level,
            $extra
        );
    }

    /**
     * @inheritdoc
     */
    public function logContextThrowable(
        AbstractMessage $message,
        \Throwable $throwable,
        string $level = LogLevel::ERROR,
        array $extra = []
    ): void
    {
        $this->logContextMessage($message, ThrowableFormatter::toString($throwable), $level, $extra);
    }

    /**
     * @inheritdoc
     */
    public function getContextThrowableCallableLogger(
        AbstractMessage $message,
        string $level = LogLevel::ERROR
    ): callable
    {
        return function(\Throwable $throwable) use ($message, $level)
        {
            $this->logContextMessage($message, ThrowableFormatter::toString($throwable), $level);
        };
    }

    /**
     * @inheritdoc
     */
    public function getThrowableCallableLogger(string $level = LogLevel::ERROR): callable
    {
        return function(\Throwable $throwable) use ($level)
        {
            ApplicationLogger::info('default', ThrowableFormatter::toString($throwable), $level);
        };
    }

    /**
     * @inheritdoc
     */
    final public function send(AbstractCommand $command, DeliveryOptions $deliveryOptions): void
    {
        $this->originContext->delivery($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    final public function delivery(AbstractMessage $message, DeliveryOptions $deliveryOptions = null): void
    {
        $this->originContext->delivery($message, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    final public function publish(AbstractEvent $event, DeliveryOptions $deliveryOptions): void
    {
        $this->originContext->publish($event, $deliveryOptions);
    }

    /**
     * Get event sourcing (aggregate) service
     *
     * @return EventSourcingService
     */
    final public function getAggregateService(): EventSourcingService
    {
        return $this->eventSourcingService;
    }

    /**
     * Get saga service
     *
     * @return SagaService
     */
    final public function getSagaService(): SagaService
    {
        return $this->sagaService;
    }

    /**
     * @inheritdoc
     */
    final public function appendCommandExecutionOptions(CommandHandlerOptions $options): void
    {
        $this->commandExecutionOptions = $options;
    }

    /**
     * @inheritdoc
     */
    final public function appendEventListenerOptions(EventListenerOptions $options): void
    {
        $this->eventListenerOptions = $options;
    }

    /**
     * @inheritdoc
     */
    final public function getCommandHandlerOptions(): CommandHandlerOptions
    {
        return $this->commandExecutionOptions;
    }

    /**
     * @inheritdoc
     */
    public function getEventListenerOptions(): EventListenerOptions
    {
        return $this->eventListenerOptions;
    }

    /**
     * Get origin context
     *
     * @return DeliveryContextInterface
     */
    final protected function getOriginContext(): DeliveryContextInterface
    {
        return $this->originContext;
    }

    /**
     * Get entry point name
     *
     * @return string
     */
    final protected function getEntryPointName()
    {
        return $this->entryPointName;
    }
}
