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
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\AbstractExecutionOptions;


/**
 * Process message (event/command)
 */
class ProcessMessageTask extends AbstractTask
{
    /**
     * Handler
     *
     * @var \Closure
     */
    private $handler;

    /**
     * @param \Closure                 $closure
     * @param AbstractExecutionOptions $options
     */
    public function __construct(\Closure $closure, AbstractExecutionOptions $options)
    {
        $this->handler = $closure;

        parent::__construct($options);
    }

    /**
     * @inheritdoc
     */
    public function __invoke(MessageInterface $message, ContextInterface $context): ?TaskInterface
    {
        $this->appendOptions($context);
        $this->logMessage($message, $context);

        $handler = $this->handler;

        return $handler($message, $context);

    }
}
