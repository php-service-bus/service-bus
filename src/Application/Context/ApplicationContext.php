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

namespace Desperado\ConcurrencyFramework\Application\Context;

use Desperado\ConcurrencyFramework\Application\Context\Exceptions\StorageManagerWasNotConfiguredException;
use Desperado\ConcurrencyFramework\Domain\Environment\Environment;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\AbstractStorageManager;
use Psr\Log\LoggerInterface;

/**
 * Application context
 */
class ApplicationContext implements DeliveryContextInterface
{
    /**
     * Context messages data
     *
     * @var ContextMessages
     */
    private $contextMessages;

    /**
     * Context storage data
     *
     * @var ContextStorage
     */
    private $contextStorage;

    /**
     * Logger context
     *
     * @var ContextLogger
     */
    private $contextLogger;

    /**
     * Context entry point
     *
     * @var ContextEntryPoint
     */
    private $contextEntryPoint;

    /**
     * @param ContextEntryPoint $contextEntryPoint
     * @param ContextMessages   $contextMessages
     * @param ContextStorage    $contextStorage
     * @param ContextLogger     $contextLogger
     */
    public function __construct(
        ContextEntryPoint $contextEntryPoint,
        ContextMessages $contextMessages,
        ContextStorage $contextStorage,
        ContextLogger $contextLogger
    )
    {
        $this->contextEntryPoint = $contextEntryPoint;
        $this->contextMessages = $contextMessages;
        $this->contextStorage = $contextStorage;
        $this->contextLogger = $contextLogger;
    }

    /**
     * Get logger
     *
     * @param string|null $channelName
     *
     * @return LoggerInterface
     */
    public function getLogger(string $channelName = null): LoggerInterface
    {
        return $this->contextLogger->getLogger($channelName);
    }

    /**
     * Get persistence manager
     *
     * @param string $entry
     *
     * @return AbstractStorageManager
     *
     * @throws StorageManagerWasNotConfiguredException
     */
    public function getStorage(string $entry): AbstractStorageManager
    {
        return $this->contextStorage->getStorage($entry);
    }

    /**
     * Get application environment
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->contextEntryPoint->getEnvironment();
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->contextMessages->deliveryMessage($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->contextMessages->deliveryMessage($event, $deliveryOptions);
    }
}
