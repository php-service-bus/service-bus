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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus;

use Desperado\ConcurrencyFramework\Domain\Behavior\BehaviorInterface;
use Desperado\ConcurrencyFramework\Domain\MessageBus\MessageBusInterface;
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineCollection;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Behavior\HandleErrorBehavior;
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
     * Behaviors collection
     *
     * @var BehaviorInterface[]
     */
    private $behaviors = [];

    /**
     * Add message handler
     *
     * @param                                  $messageNamespace
     * @param \Closure                         $handler
     * @param Options\AbstractExecutionOptions $options
     *
     * @return void
     */
    public function addMessageHandler(
        string $messageNamespace,
        \Closure $handler,
        Options\AbstractExecutionOptions $options
    ): void
    {
        $this->messageHandlers[$messageNamespace][] = [
            'handler' => $handler,
            'options' => $options
        ];
    }

    /**
     * Add behavior
     *
     * @param BehaviorInterface $behavior
     *
     * @return void
     */
    public function addBehavior(BehaviorInterface $behavior): void
    {
        $this->behaviors[\get_class($behavior)] = $behavior;
    }

    /**
     * Add error handler
     *
     * @param string   $forExceptionNamespace
     * @param string   $forCommandNamespace
     * @param \Closure $handler
     *
     * @return void
     */
    public function addErrorHandler(
        string $forExceptionNamespace,
        string $forCommandNamespace,
        \Closure $handler
    ): void
    {
        $this->errorHandlers[$forCommandNamespace][$forExceptionNamespace] = $handler;
    }

    /**
     * Build messages bus
     *
     * @return MessageBusInterface
     */
    public function build(): MessageBusInterface
    {
        if(true === \array_key_exists(HandleErrorBehavior::class, $this->behaviors))
        {
            /** @var HandleErrorBehavior $behavior */
            $behavior = $this->behaviors[HandleErrorBehavior::class];
            $behavior->appendHandlers($this->errorHandlers);
        }

        $collection = new PipelineCollection();

        foreach($this->messageHandlers as $messageNamespace => $handlers)
        {
            $pipeline = new Pipeline($messageNamespace);

            foreach($handlers as $handlerData)
            {
                $task = new ProcessMessageTask(
                    $handlerData['handler'],
                    $handlerData['options']
                );

                foreach($this->behaviors as $behavior)
                {
                    $task = $behavior->apply($pipeline, $task);
                }

                $pipeline->push($task);
            }

            $collection->add($pipeline);
        }

        return new MessageBus($collection);
    }
}
