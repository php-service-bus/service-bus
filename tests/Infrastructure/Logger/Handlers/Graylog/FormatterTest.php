<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Logger\Handlers\Graylog;

use function ServiceBus\Common\datetimeInstantiator;
use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Logger\Handlers\Graylog\Formatter;
use function ServiceBus\Common\jsonDecode;

/**
 *
 */
final class FormatterTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function format(): void
    {
        $message = \str_repeat('x', 40000);

        $result = (new Formatter('test_host'))->format([
            'level'    => 100,
            'datetime' => datetimeInstantiator('2019-02-15 15:32:19'),
            'message'  => $message,
            'context'  => [
                'contextKey' => 'contextValue',
                'secondKey'  => null,
                'largeKey'   => \str_repeat('x', 40000),
                'key' => ['qwerty' => 'root'],
            ],
            'extra'    => [
                'file' => '/src/tests.php',
                'line' => __LINE__,
            ],
        ]);

        static::assertSame(
            jsonDecode(\file_get_contents(__DIR__ . '/expected_format_result.json')),
            $result
        );
    }
}
