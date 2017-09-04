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

use Desperado\Framework\Domain\Environment\Environment;
use Desperado\Framework\Domain\Messages\CommandInterface;
use Desperado\Framework\Domain\Messages\EventInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\Framework\Infrastructure\CQRS\Context\MessageExecutionOptionsContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\Options;
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
