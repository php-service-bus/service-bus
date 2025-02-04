<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Infrastructure\Logger\Handlers\Graylog;

use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Logger\Handlers\Graylog\Formatter;

use function ServiceBus\Common\datetimeInstantiator;
use function ServiceBus\Common\jsonDecode;

/**
 *
 */
final class FormatterTest extends TestCase
{
    /**
     * @test
     */
    public function format(): void
    {
        $message = \str_repeat('x', 40000);

        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2019-02-15 15:32:19'),
            channel: 'test',
            level: Logger::toMonologLevel(100),
            message: $message,
            context: [
                'contextKey' => 'contextValue',
                'secondKey'  => null,
                'largeKey'   => \str_repeat('x', 40000),
                'key' => ['qwerty' => 'root'],
            ],
            extra: [
                'file' => '/src/tests.php',
                'line' => __LINE__,
            ],
        );

        $result = (new Formatter('test_host'))->format($record);

        self::assertSame(
            jsonDecode(\file_get_contents(__DIR__ . '/expected_format_result.json')),
            $result
        );
    }
}
