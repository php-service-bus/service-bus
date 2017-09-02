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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context;

use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\MessageOptionsInterface;

/**
 * Execution options
 */
interface MessageExecutionOptionsContextInterface
{
    /**
     * Append command handler execution options
     *
     * @param Options\CommandOptions $options
     *
     * @return void
     */
    public function appendCommandExecutionOptions(Options\CommandOptions $options): void;

    /**
     * Append event handler execution options
     *
     * @param Options\EventOptions $options
     *
     * @return void
     */
    public function appendEventExecutionOptions(Options\EventOptions $options): void;

    /**
     * Get options for message
     *
     * @param MessageInterface $message
     *
     * @return MessageOptionsInterface
     */
    public function getOptions(MessageInterface $message): Options\MessageOptionsInterface;
}
