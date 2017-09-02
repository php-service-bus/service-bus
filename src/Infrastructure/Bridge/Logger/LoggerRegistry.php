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

namespace Desperado\ConcurrencyFramework\Infrastructure\Bridge\Logger;

use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Logger\Handlers\StdoutHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Processor;
use Monolog\Logger;

/**
 * Logger registry
 */
class LoggerRegistry
{
    /**
     * List of all loggers in the registry (by named indexes)
     *
     * @var Logger[]
     */
    private static $loggersCollection = [];

    /**
     * Handlers list
     *
     * @var HandlerInterface[]
     */
    private static $handlers;

    /**
     * Gets Logger instance from the registry
     *
     * @param string $channelName Name of the requested Logger instance
     *
     * @throws \InvalidArgumentException
     *
     * @return Logger
     */
    public static function getLogger(string $channelName = null): Logger
    {
        $channelName = $channelName
            ?:
            ('' !== (string) \getenv('ENTRY_POINT_NAME')
                ? \getenv('ENTRY_POINT_NAME')
                : 'default'
            );

        if(false === self::has($channelName))
        {
            self::$loggersCollection[$channelName] = self::getDefaultInstance($channelName);
        }

        return self::$loggersCollection[$channelName];
    }

    /**
     * Setup handlers
     *
     * @param array $handlers
     */
    public static function setupHandlers(array $handlers): void
    {
        self::$handlers = $handlers;
    }

    /**
     * Remove all loggers
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$loggersCollection = [];
    }

    /**
     * Enable channel with specified handlers
     *
     * @param string             $channelName
     * @param HandlerInterface[] $handlers
     * @param callable[]         $processors
     *
     * @return Logger
     */
    public static function enableChannel(string $channelName, array $handlers, array $processors = []): Logger
    {
        $processors = 0 !== count($processors) ? $processors : self::getDefaultProcessors();

        self::$loggersCollection[$channelName] = new Logger($channelName, $handlers, $processors);

        return self::$loggersCollection[$channelName];
    }

    /**
     * Checks if such logging channel exists by channel name
     *
     * @param string $channelName
     *
     * @return bool
     */
    protected static function has(string $channelName): bool
    {
        return isset(self::$loggersCollection[$channelName]);
    }

    /**
     * Get default channel instance
     *
     * @param string $channelName
     *
     * @return Logger
     */
    protected static function getDefaultInstance(string $channelName): Logger
    {
        return self::enableChannel($channelName, self::getHandlers());
    }

    /**
     * Get default processors
     *
     * @return callable[]
     */
    protected static function getDefaultProcessors(): array
    {
        return [
            new Processor\ProcessIdProcessor()
        ];
    }

    /**
     * Get default handlers
     * Create console handler if default list is empty
     *
     * @return HandlerInterface[]
     */
    protected static function getHandlers(): array
    {
        if(0 === count(self::$handlers))
        {
            self::$handlers = [new StdoutHandler()];
        }

        return self::$handlers;
    }
}
