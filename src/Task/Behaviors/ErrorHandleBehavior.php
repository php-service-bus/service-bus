<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task\Behaviors;

use Desperado\ServiceBus\Task\Middleware\ErrorHandlerMiddleware;
use Symfony\Component\Debug\ErrorHandler;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\Task\TaskInterface;

/**
 * Adding exception handlers when executing messages
 */
class ErrorHandleBehavior implements BehaviorInterface
{
    /**
     * Error handlers
     *
     * @var Handlers\Exceptions\ExceptionHandlersCollection
     */
    private $exceptionHandlersCollection;

    /**
     * Create behavior
     *
     * @param Handlers\Exceptions\ExceptionHandlersCollection|null $exceptionHandlersCollection
     *
     * @return ErrorHandleBehavior
     */
    public static function create(Handlers\Exceptions\ExceptionHandlersCollection $exceptionHandlersCollection = null): self
    {
        $self = new self();

        $self->exceptionHandlersCollection = $exceptionHandlersCollection ??
            Handlers\Exceptions\ExceptionHandlersCollection::create();

        return $self;
    }

    /**
     * @param Handlers\Exceptions\ExceptionHandlerData $exceptionHandlerData
     *
     * @return void
     */
    public function append(Handlers\Exceptions\ExceptionHandlerData$exceptionHandlerData): void
    {
        $this->exceptionHandlersCollection->add($exceptionHandlerData);
    }

    /**
     * @inheritdoc
     */
    public function apply(TaskInterface $task): TaskInterface
    {
        return new ErrorHandlerMiddleware($task, $this->exceptionHandlersCollection);
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        if(true === \class_exists('Symfony\Component\Debug\ErrorHandler'))
        {
            ErrorHandler::register();
        }
    }
}
