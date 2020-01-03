<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Logger\Handlers\StdOut;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ServiceBus\Infrastructure\Logger\Handlers\StdOut\StdOutFormatter;

/**
 *
 */
final class StdOutFormatterTest extends TestCase
{
    /**
     * @test
     * @dataProvider logDataProvider
     */
    public function log(string $message, string $level, string $expected): void
    {
        $logHandler = new TestHandler();
        $logHandler->setFormatter(
            new StdOutFormatter('%channel%.%level_name%: %message% %context% %extra%')
        );

        $logger = new Logger('tests', [$logHandler]);

        $logger->log($level, $message);

        $messages = $logHandler->getRecords();

        $message = \end($messages);

        static::assertSame($expected, $message['formatted'], 'tests.emergency: qwerty [] []');
    }

    public function logDataProvider(): array
    {
        return [
            ['qwerty', LogLevel::EMERGENCY, "\e[1mtests\e[0m.\e[1;31memergency\e[0m: qwerty [] []"],
            ['qwerty', LogLevel::WARNING, "\e[1mtests\e[0m.\e[1;33mwarning\e[0m: qwerty [] []"],
            ['qwerty', LogLevel::NOTICE, "\e[1mtests\e[0m.\e[1;32mnotice\e[0m: qwerty [] []"],
            ['qwerty', LogLevel::INFO, "\e[1mtests\e[0m.\e[1;35minfo\e[0m: qwerty [] []"],
            ['qwerty', LogLevel::DEBUG, "\e[1mtests\e[0m.\e[1;36mdebug\e[0m: qwerty [] []"]
        ];
    }
}
