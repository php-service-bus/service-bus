<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Kernel\Events;

use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\ServiceBus\Application\EntryPoint\EntryPointContext;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base event handling class
 */
abstract class AbstractMessageFlowEvent extends Event
{
    /**
     * Entry point message context
     *
     * @var EntryPointContext
     */
    private $entryPointContext;

    /**
     * Context of message processing
     *
     * @var ExecutionContextInterface
     */
    private $executionContext;

    /**
     * @param EntryPointContext         $entryPointContext
     * @param ExecutionContextInterface $executionContext
     */
    public function __construct(EntryPointContext $entryPointContext, ExecutionContextInterface $executionContext)
    {
        $this->entryPointContext = $entryPointContext;
        $this->executionContext = $executionContext;
    }

    /**
     * Get entry point message context
     *
     * @return EntryPointContext
     */
    final public function getEntryPointContext(): EntryPointContext
    {
        return $this->entryPointContext;
    }

    /**
     * Get context of message processing
     *
     * @return ExecutionContextInterface
     */
    final public function getExecutionContext(): ExecutionContextInterface
    {
        return $this->executionContext;
    }
}
