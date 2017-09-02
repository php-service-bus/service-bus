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

namespace Desperado\Framework\Domain\Task;

use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\Options\AbstractExecutionOptions;

/**
 * Task
 */
interface TaskInterface
{
    /**
     * Execute task
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return TaskInterface|null
     */
    public function __invoke(MessageInterface $message, ContextInterface $context): ?TaskInterface;

    /**
     * Get execute options
     *
     * @return AbstractExecutionOptions
     */
    public function getOptions(): AbstractExecutionOptions;
}
