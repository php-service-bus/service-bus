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
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineCollection;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Pipeline\Pipeline;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Task\ProcessMessageTask;

/**
 * Message bus factory
 */
class MessageBusBuilder
{
    /**
     * Message handlers
     *
     * @var array
     */
    private $messageHandlers = [];

    /**
     * Error handlers
     *
     * @var array
     */
    private $errorHandlers = [];

    /**
     * Add command execution handler
     *
     * @param \Closure               $handler
     * @param Options\CommandOptions $options
     *
     * @return void
     */
    public function addCommandHandler(\Closure $handler, Options\CommandOptions $options): void
    {
        $this->messageHandlers['command'][] = [
            'handler' => $handler,
            'options' => $options
        ];
    }

    /**
     * Add event execution handler
     *
     * @param \Closure             $handler
     * @param Options\EventOptions $options
     *
     * @return void
     */
    public function addEventHandler(\Closure $handler, Options\EventOptions $options): void
    {
        $this->messageHandlers['event'][] = [
            'handler' => $handler,
            'options' => $options
        ];
    }

    /**
     * Add error handler
     *
     * @param string               $forCommandNamespace
     * @param \Closure             $handler
     * @param Options\ErrorOptions $options
     *
     * @return void
     */
    public function addErrorHandler(
        string $forCommandNamespace,
        \Closure $handler,
        Options\ErrorOptions $options
    ): void
    {
        $this->errorHandlers[$forCommandNamespace] = [
            'handler' => $handler,
            'options' => $options
        ];
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

            foreach($handlers as $handlerData)
            {
                $pipeline->push(
                    new ProcessMessageTask(
                        $handlerData['handler'],
                        $handlerData['options']
                    )
                );
            }

            $collection->add($pipeline);
        }

        return new MessageBus($collection);
    }
}
