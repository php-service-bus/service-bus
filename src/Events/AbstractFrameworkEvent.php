<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Events;

use Desperado\Domain\Message\MessageInterface;
use Desperado\Framework\Application\AbstractApplicationContext;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base framework event class
 */
abstract class AbstractFrameworkEvent extends Event
{
    /**
     * Execution message
     *
     * @var MessageInterface
     */
    private $message;

    /**
     * Execution context
     *
     * @var AbstractApplicationContext
     */
    private $executionContext;

    /**
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $executionContext
     */
    public function __construct(MessageInterface $message, AbstractApplicationContext $executionContext)
    {
        $this->message = $message;
        $this->executionContext = $executionContext;
    }

    /**
     * Get message
     *
     * @return MessageInterface
     */
    final public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * Get execution context
     *
     * @return AbstractApplicationContext
     */
    final public function getExecutionContext(): AbstractApplicationContext
    {
        return $this->executionContext;
    }
}
