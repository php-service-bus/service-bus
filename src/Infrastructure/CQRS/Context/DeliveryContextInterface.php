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

namespace Desperado\Framework\Infrastructure\CQRS\Context;

use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\Messages\CommandInterface;
use Desperado\Framework\Domain\Messages\EventInterface;

/**
 * Delivery context
 */
interface DeliveryContextInterface extends ContextInterface
{
    /**
     * Send command
     *
     * @param CommandInterface $command
     * @param DeliveryOptions  $deliveryOptions
     *
     * @return void
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void;

    /**
     * Publish event
     *
     * @param EventInterface  $event
     * @param DeliveryOptions $deliveryOptions
     *
     * @return void
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void;
}
