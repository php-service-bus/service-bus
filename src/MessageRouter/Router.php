<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\MessageRouter;

use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Common\Messages\Command;
use ServiceBus\Common\Messages\Event;
use ServiceBus\Common\Messages\Message;
use ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified;
use ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified;
use ServiceBus\MessageRouter\Exceptions\MultipleCommandHandlersNotAllowed;

/**
 *
 */
final class Router implements \Countable
{
    /**
     * Event listeners
     *
     * @var array<string, array<string|int, \ServiceBus\Common\MessageExecutor\MessageExecutor>>
     */
    private $eventListenersMap = [];

    /**
     * Command handlers
     *
     * @var array<string, \ServiceBus\Common\MessageExecutor\MessageExecutor>
     */
    private $commandHandlersMap = [];

    /**
     * Registered handlers count
     *
     * @var int
     */
    private $handlersCount = 0;

    /**
     * Add event listener
     * For each event there can be many listeners
     *
     * @param Event|string    $event Event object or class
     * @param MessageExecutor $handler
     *
     * @return void
     *
     * @throws \ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified
     */
    public function registerListener($event, MessageExecutor $handler): void
    {
        $eventClass = $event instanceof Event
            ? \get_class($event)
            : (string) $event;

        if('' !== $eventClass && true === \class_exists($eventClass))
        {
            $this->eventListenersMap[$eventClass][] = $handler;
            $this->handlersCount++;

            return;
        }

        throw new InvalidEventClassSpecified('The event class is not specified, or does not exist');
    }

    /**
     * Register command handler
     * For 1 command there can be only 1 handler
     *
     * @param Command|string  $command Command object or class
     * @param MessageExecutor $handler
     *
     * @return void
     *
     * @throws \ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified
     * @throws \ServiceBus\MessageRouter\Exceptions\MultipleCommandHandlersNotAllowed
     */
    public function registerHandler($command, MessageExecutor $handler): void
    {
        $commandClass = $command instanceof Command
            ? \get_class($command)
            : (string) $command;

        if('' === $commandClass || false === \class_exists($commandClass))
        {
            throw new InvalidCommandClassSpecified('The command class is not specified, or does not exist');
        }

        if(true === isset($this->commandHandlersMap[$commandClass]))
        {
            throw new MultipleCommandHandlersNotAllowed(
                \sprintf('A handler has already been registered for the "%s" command', $commandClass)
            );
        }

        $this->commandHandlersMap[$commandClass] = $handler;
        $this->handlersCount++;
    }

    /**
     * @param Message $message
     *
     * @return array<mixed, \ServiceBus\Common\MessageExecutor\MessageExecutor>
     */
    public function match(Message $message): array
    {
        $messageClass = \get_class($message);

        if($message instanceof Event)
        {
            return $this->eventListenersMap[$messageClass] ?? [];
        }

        return true === isset($this->commandHandlersMap[$messageClass])
            ? [$this->commandHandlersMap[$messageClass]]
            : [];
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return $this->handlersCount;
    }
}
