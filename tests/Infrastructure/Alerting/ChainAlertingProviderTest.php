<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Alerting;

use Amp\Failure;
use Amp\Promise;
use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Alerting\AlertContext;
use ServiceBus\Infrastructure\Alerting\AlertingProvider;
use ServiceBus\Infrastructure\Alerting\AlertMessage;
use ServiceBus\Infrastructure\Alerting\ChainAlertingProvider;
use function Amp\call;
use function Amp\File\put;

/**
 *
 */
final class ChainAlertingProviderTest extends TestCase
{
    /** @test */
    public function flow(): void
    {
        $expectedFilePath = \sys_get_temp_dir() . '/' . \sha1(__METHOD__);

        @\unlink($expectedFilePath);

        $first = new class() implements AlertingProvider {
            public function send(AlertMessage $message, ?AlertContext $context = null): Promise
            {
                return new Failure(new \RuntimeException('qwerty'));
            }
        };

        $second = new class($expectedFilePath) implements AlertingProvider {
            /** @var string */
            private $expectedFilePath;

            public function __construct($expectedFilePath)
            {
                $this->expectedFilePath = $expectedFilePath;
            }

            public function send(AlertMessage $message, ?AlertContext $context = null): Promise
            {
                return call(
                    function() use ($message): \Generator
                    {
                        yield put($this->expectedFilePath, $message->content);
                    }
                );
            }
        };

        $chainProvider = new ChainAlertingProvider([$first, $second]);

        Promise\wait($chainProvider->send(new AlertMessage('qwerty')));

        static::assertFileExists($expectedFilePath);

        @\unlink($expectedFilePath);
    }
}
