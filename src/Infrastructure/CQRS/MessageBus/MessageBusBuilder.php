<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus;

use Desperado\ConcurrencyFramework\Domain\MessageBus\MessageBusInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineCollection;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus\Exceptions\MessageBusCreateFailException;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Pipeline\Pipeline;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Task\ProcessMessageTask;

/**
 * Message bus factory
 */
class MessageBusBuilder
{
    /**
     * Handlers
     *
     * [
     *    'commands' => [
     *         'someCommandNamespace' => \Closure[]
     *     ],
     *    'events' => [
     *         'someEventNamespace' => \Closure[]
     *     ],
     *    'errors' => [
     *         'someErrorNamespace' => \Closure
     *    ]
     * ]
     *
     * @var array
     */
    private $messageHandlers = [];

    /**
     * Add command handler
     *
     * @param string   $commandType
     * @param \Closure $handler
     *
     * @return $this
     */
    public function addCommandHandler(string $commandType, \Closure $handler): self
    {
        self::guardCommandType($commandType);

        $this->messageHandlers['commands'][$commandType] = $handler;

        return $this;
    }

    /**
     * Add event listener
     *
     * @param string   $eventType
     * @param \Closure $listener
     *
     * @return $this
     */
    public function addEventListener(string $eventType, \Closure $listener): self
    {
        self::guardEventType($eventType);

        $this->messageHandlers['events'][$eventType] = $listener;

        return $this;
    }

    /**
     * Add error handler
     *
     * @param string   $errorType
     * @param string   $commandType
     * @param \Closure $handler
     *
     * @return $this
     */
    public function addErrorHandler(string $errorType, string $commandType, \Closure $handler): self
    {
        self::guardErrorType($errorType);
        self::guardCommandType($commandType);

        $this->messageHandlers['errors'][$commandType][$errorType] = $handler;

        return $this;
    }

    /**
     * Build messages bus
     *
     * @return MessageBusInterface
     */
    public function build(): MessageBusInterface
    {
        $collection = new PipelineCollection();

        foreach($this->messageHandlers as $type => $handlers)
        {
            $pipeline = new Pipeline($type);

            foreach($handlers as $key => $handler)
            {
                if(true === \is_object($handler))
                {
                    $pipeline->push(new ProcessMessageTask($handler));
                }
            }

            $collection->add($pipeline);
        }

        return new MessageBus($collection);
    }

    /**
     * Assert event listener is valid
     *
     * @param string $eventType
     *
     * @throws MessageBusCreateFailException
     */
    private static function guardEventType(string $eventType): void
    {
        self::guardHandlerClassNamespace($eventType, 'Event');

        if(false === \is_a($eventType, EventInterface::class, true))
        {
            throw new MessageBusCreateFailException(
                \sprintf('Event must be instanceof "%s"', EventInterface::class)
            );
        }
    }

    /**
     * Assert command handler is valid
     *
     * @param string $commandType
     *
     * @throws MessageBusCreateFailException
     */
    private static function guardCommandType(string $commandType): void
    {
        self::guardHandlerClassNamespace($commandType, 'Command');

        if(false === \is_a($commandType, CommandInterface::class, true))
        {
            throw new MessageBusCreateFailException(
                \sprintf('Command must be instanceof "%s"', CommandInterface::class)
            );
        }
    }

    /**
     * Assert error handler is valid
     *
     * @param string $errorType
     *
     * @throws MessageBusCreateFailException
     */
    private static function guardErrorType(string $errorType): void
    {
        self::guardHandlerClassNamespace($errorType, 'Error');

        if(false === \is_a($errorType, \Throwable::class, true))
        {
            throw new MessageBusCreateFailException(
                \sprintf('Error must be instanceof "%s"', \Throwable::class)
            );
        }
    }

    /**
     * Assert handler class namespace is valid
     *
     * @param string $classNamespace
     * @param string $type
     *
     * @throws MessageBusCreateFailException
     */
    private static function guardHandlerClassNamespace(string $classNamespace, string $type): void
    {
        if('' === $classNamespace)
        {
            throw new MessageBusCreateFailException(
                \sprintf('%s class must be specified', $type)
            );
        }

        if(false === \class_exists($classNamespace))
        {
            throw new MessageBusCreateFailException(
                \sprintf(
                    '%s class not found (specified namespace: "%s")',
                    $type, $classNamespace
                )
            );
        }
    }
}
