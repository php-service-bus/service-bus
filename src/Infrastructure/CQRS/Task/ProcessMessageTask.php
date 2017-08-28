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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Task;

use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;


/**
 * Process message (event/command)
 */
class ProcessMessageTask implements TaskInterface
{
    /**
     * Message handler
     *
     * @var \Closure
     */
    private $handlerClosure;

    /**
     * @param \Closure $handlerClosure
     */
    public function __construct(\Closure $handlerClosure)
    {
        $this->handlerClosure = $handlerClosure;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(MessageInterface $message, ContextInterface $context): ?TaskInterface
    {
        return \call_user_func_array($this->handlerClosure, [$message, $context]);
    }
}
