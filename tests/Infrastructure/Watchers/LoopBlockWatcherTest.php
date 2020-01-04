<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Watchers;

use Amp\Loop;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Watchers\LoopBlockWatcher;
use function Amp\delay;
use function ServiceBus\Tests\filterLogMessages;

/**
 *
 */
final class LoopBlockWatcherTest extends TestCase
{
    /** @test */
    public function listenWithoutBlock(): void
    {
        $testHandler = new TestHandler();
        $logger      = new Logger('tests', [$testHandler]);

        $watcher = new LoopBlockWatcher($logger);

        Loop::run(
            static function () use ($watcher): \Generator
            {
                $watcher->run();

                yield delay(500);

                Loop::stop();
            }
        );

        static::assertCount(1, $testHandler->getRecords());
    }

    /** @test */
    public function listenWithBlock(): void
    {
        $testHandler = new TestHandler();
        $logger      = new Logger('tests', [$testHandler]);

        $watcher = new LoopBlockWatcher($logger);

        Loop::run(
            static function () use ($watcher): void
            {
                $watcher->run();


                Loop::repeat(
                    0,
                    static function ()
                    {
                        \usleep(100 * 1000);
                    }
                );

                Loop::delay(
                    300,
                    static function ()
                    {
                        Loop::stop();
                    }
                );
            }
        );

        static::assertContains(
            'A lock event loop has been detected. Blocking time: {lockTime} seconds',
            filterLogMessages($testHandler)
        );
    }
}
