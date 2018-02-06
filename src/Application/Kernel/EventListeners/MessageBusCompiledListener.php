<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Kernel\EventListeners;

use Desperado\ServiceBus\Application\Kernel\Events\MessageBusCompiledEvent;
use Psr\Log\LoggerInterface;

/**
 * Message bus successful compiled
 */
class MessageBusCompiledListener
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param MessageBusCompiledEvent $event
     *
     * @return void
     */
    public function onCompiled(MessageBusCompiledEvent $event): void
    {
        $this->logger->info(
            \sprintf(
                'The message bus has been successfully configured. Total number of handlers: %s',
                $event->getTaskCount()
            )
        );
    }
}
