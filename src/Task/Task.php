<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\ServiceBus\Services\Handlers\AbstractMessageExecutionParameters;
use React\Promise\PromiseInterface;

/**
 * The task to be executed
 */
class Task implements TaskInterface
{
    /**
     * Message handler
     *
     * @var \Closure
     */
    private $executionHandler;

    /**
     * Task-specific parameters
     *
     * @var AbstractMessageExecutionParameters
     */
    private $options;

    /**
     * Create a new task
     *
     * @param \Closure                           $executionHandler
     * @param AbstractMessageExecutionParameters $options
     *
     * @return self
     */
    public static function new(\Closure $executionHandler, AbstractMessageExecutionParameters $options): self
    {
        $self = new self();

        $self->executionHandler = $executionHandler;
        $self->options = $options;

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): AbstractMessageExecutionParameters
    {
        return $this->options;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(
        AbstractMessage $message,
        ExecutionContextInterface $context,
        array $additionalArguments = []
    ): ?PromiseInterface
    {
        $parameters = [$message, $context];

        foreach($additionalArguments as $argument)
        {
            $parameters[] = $argument;
        }

        return \call_user_func_array($this->executionHandler, $parameters);
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
