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

namespace Desperado\ConcurrencyFramework\Domain\EventSourced;

use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;

/**
 * Saga
 */
interface SagaInterface
{
    public const DEFAULT_EXPIRE_PERIOD = '+1 hour';

    /**
     * Start saga
     *
     * @param CommandInterface $command
     *
     * @return $this
     */
    public static function start(CommandInterface $command);

    /**
     * Reset fired commands
     *
     * @return void
     */
    public function resetCommands(): void;

    /**
     * Get fired commands
     *
     * @return array
     */
    public function getCommands(): array;

    /**
     * Get state
     *
     * @return SagaStateInterface
     */
    public function getState(): SagaStateInterface;
}
