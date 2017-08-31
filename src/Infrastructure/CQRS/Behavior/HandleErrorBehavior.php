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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Behavior;

use Desperado\ConcurrencyFramework\Domain\Behavior\BehaviorInterface;
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Task\ErrorHandlerWrappedTask;

/**
 * Run error handler for failed command (if specified)
 */
class HandleErrorBehavior implements BehaviorInterface
{
    /**
     * Error handlers
     *
     * @var array
     */
    private $handlers = [];

    /**
     * Append error handlers
     *
     * @param array $errorHandlers
     *
     * @return void
     */
    public function appendHandlers(array $errorHandlers): void
    {
        $this->handlers = \array_merge($this->handlers, $errorHandlers);
    }

    /**
     * @inheritdoc
     */
    public function apply(PipelineInterface $pipeline, TaskInterface $task): TaskInterface
    {
        return new ErrorHandlerWrappedTask($task, $this->handlers);
    }
}
