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

namespace Desperado\ConcurrencyFramework\Infrastructure\Application;

use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;

/**
 * Kernel
 */
interface KernelInterface
{
    /**
     * Handle message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return void
     */
    public function handleMessage(MessageInterface $message, ContextInterface $context): void;
}
