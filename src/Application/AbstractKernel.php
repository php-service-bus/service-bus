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

namespace Desperado\ConcurrencyFramework\Application;

use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Application\KernelInterface;

/**
 * Base application class
 */
abstract class AbstractKernel implements KernelInterface
{
    /**
     * Is application booted
     *
     * @var bool
     */
    private $booted = false;

    /**
     * @inheritdoc
     */
    public function handleMessage(MessageInterface $message, ContextInterface $context): void
    {
        echo '111';
    }

    public function boot(): void
    {
        if(true === $this->booted)
        {
            return;
        }
    }
}
