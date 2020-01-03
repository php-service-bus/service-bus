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
use ServiceBus\Infrastructure\Watchers\GarbageCollectorWatcher;
use function Amp\delay;
use function ServiceBus\Tests\filterLogMessages;

/**
 *
 */
final class GarbageCollectorWatcherTest extends TestCase
{
    /** @test */
    public function register(): void
    {
        $testHandler = new TestHandler();
        $logger      = new Logger('tests', [$testHandler]);

        $watcher = new GarbageCollectorWatcher(200, $logger);

        Loop::run(
            static function() use ($watcher): \Generator
            {
                $watcher->run();

                yield delay(300);

                unset($watcher);

                Loop::stop();
            }
        );

        static::assertContains(
            'Forces collection of any existing garbage cycles',
            filterLogMessages($testHandler)
        );
    }
}
