<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\KernelEvents;

use Desperado\ServiceBus\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
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
     * @var AbstractExecutionContext
     */
    private $executionContext;

    /**
     * @param EntryPointContext        $entryPointContext
     * @param AbstractExecutionContext $executionContext
     */
    public function __construct(EntryPointContext $entryPointContext, AbstractExecutionContext $executionContext)
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
     * @return AbstractExecutionContext
     */
    final public function getExecutionContext(): AbstractExecutionContext
    {
        return $this->executionContext;
    }
}
