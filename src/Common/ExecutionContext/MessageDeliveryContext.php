<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Common\ExecutionContext;

use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 *
 */
interface MessageDeliveryContext
{
    /**
     * Execute simple messages (commands\events) delivery
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param Message ...$messages
     *
     * @return Promise<null>
     */
    public function delivery(Message ...$messages): Promise;

    /**
     * Send command with specified options
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param Command            headers
     * @param array $headers
     *
     * @return Promise<null>
     */
    public function send(Command $command, array $headers = []): Promise;

    /**
     * Publish event with specified headers
     *
     * @param Event $event
     * @param array $headers
     *
     * @return Promise<null>
     */
    public function publish(Event $event, array $headers = []): Promise;
}
