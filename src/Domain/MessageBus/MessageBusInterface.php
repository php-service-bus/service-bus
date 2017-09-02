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

namespace Desperado\ConcurrencyFramework\Domain\MessageBus;

use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;

/**
 * Message bus
 */
interface MessageBusInterface
{
    /**
     * Handle message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return void
     */
    public function handle(MessageInterface $message, ContextInterface $context): void;
}
