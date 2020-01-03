<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Retry;

use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Retry\OperationRetryWrapper;
use ServiceBus\Infrastructure\Retry\RetryOptions;
use function Amp\Promise\wait;

/**
 *
 */
final class OperationRetryWrapperTest extends TestCase
{
    /** @test */
    public function failedRetry(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectDeprecationMessage('qwerty');

        $closure = static function()
        {
            throw new \LogicException('qwerty');
        };

        $wrapper = new OperationRetryWrapper(new RetryOptions(2, 300));

        wait($wrapper($closure, \LogicException::class));
    }
}