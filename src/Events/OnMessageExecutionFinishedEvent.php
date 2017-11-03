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

use Desperado\Domain\Messages\MessageInterface;
use Desperado\Framework\Application\AbstractApplicationContext;

/**
 * Message execution completed
 */
final class OnMessageExecutionFinishedEvent extends AbstractFrameworkEvent
{
    /**
     * Message execution time
     *
     * @var float
     */
    private $executionTime;

    /**
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $executionContext
     * @param float                      $executionTime
     */
    public function __construct(
        MessageInterface $message,
        AbstractApplicationContext $executionContext,
        float $executionTime
    )
    {
        parent::__construct($message, $executionContext);

        $this->executionTime = $executionTime;
    }

    /**
     * Get message execution time
     *
     * @return float
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
}
