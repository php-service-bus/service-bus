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

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\AbstractExecutionOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\CommandOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\ErrorOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\EventOptions;


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
     * Execution options
     *
     * @var AbstractExecutionOptions
     */
    private $options;

    /**
     * @param \Closure                 $handlerClosure
     * @param AbstractExecutionOptions $options
     */
    public function __construct(\Closure $handlerClosure, AbstractExecutionOptions $options)
    {
        $this->handlerClosure = $handlerClosure;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(MessageInterface $message, ContextInterface $context): ?TaskInterface
    {
        if($context instanceof KernelContext)
        {
            $this->appendOptions($context);
        }

        return \call_user_func_array($this->handlerClosure, [$message, $context]);
    }

    /**
     * Append execution options
     *
     * @param KernelContext $context
     *
     * @return void
     */
    private function appendOptions(KernelContext $context)
    {
        switch(\get_class($this->options))
        {
            case CommandOptions::class:
                $context->appendCommandExecutionOptions($this->options);
                break;

            case EventOptions::class:
                $context->appendEventExecutionOptions($this->options);
                break;

            case ErrorOptions::class:
                $context->appendErrorHandlerExecutionOptions($this->options);
                break;
        }
    }
}
