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

namespace Desperado\ConcurrencyFramework\Domain\Task;

use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;

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
}
