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

namespace Desperado\ServiceBus\Infrastructure\Watchers;

use Kelunik\LoopBlock\BlockDetector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * DO NOT USE IN PRODUCTION environment!
 *
 * @codeCoverageIgnore
 */
final class LoopBlockWatcher
{
    /** Tick duration threshold in milliseconds */
    private const BLOCK_THRESHOLD = 10;

    /** Check interval, only check one tick every $interval milliseconds */
    private const CHECK_INTERVAL = 0;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BlockDetector|null
     */
    private $detector;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __destruct()
    {
        $this->getDetector()->stop();
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->getDetector()->start();

        $this->logger->info('Watching the event loop is running', [
            'blockThreshold' => self::BLOCK_THRESHOLD,
            'checkInterval'  => self::CHECK_INTERVAL
        ]);
    }

    /**
     * @return BlockDetector
     */
    private function getDetector(): BlockDetector
    {
        if(null === $this->detector)
        {
            $this->detector = new BlockDetector(
                self::createOnBlockHandler($this->logger),
                self::BLOCK_THRESHOLD,
                self::CHECK_INTERVAL
            );
        }

        return $this->detector;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return callable(int):void
     */
    private static function createOnBlockHandler(LoggerInterface $logger): callable
    {
        return static function(int $blockInterval) use ($logger): void
        {
            $trace     = \debug_backtrace();
            $traceData = ['info' => []];

            /** skip first since it's always the current method */
            \array_shift($trace);
            /** the call_user_func call is also skipped */
            \array_shift($trace);

            $i = 0;

            while(isset($trace[$i]['class']))
            {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $traceData['info'] = \array_merge(
                    $traceData['info'],
                    [
                        'file'     => $trace[$i - 1]['file'] ?? null,
                        'line'     => $trace[$i - 1]['line'] ?? null,
                        'class'    => $trace[$i]['class'] ?? null,
                        'function' => $trace[$i]['function'] ?? null
                    ]
                );

                $i++;
            }

            $traceData['lockTime'] = 0 !== $blockInterval ? $blockInterval / 1000 : 0;

            $logger->error('A lock event loop has been detected. Blocking time: {lockTime} seconds', $traceData);

            unset($traceData, $trace, $i);
        };
    }
}
