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

/**
 * Flush storages failed
 */
class OnFlushExecutionFailedEvent extends AbstractFrameworkEvent
{
    /**
     * Throwable instance
     *
     * @var \Throwable
     */
    private $throwable;

    /**
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $executionContext
     * @param \Throwable                 $throwable
     */
    public function __construct(
        MessageInterface $message,
        AbstractApplicationContext $executionContext,
        \Throwable $throwable
    )
    {
        parent::__construct($message, $executionContext);

        $this->throwable = $throwable;
    }

    /**
     * Get throwable instance
     *
     * @return \Throwable
     */
    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
