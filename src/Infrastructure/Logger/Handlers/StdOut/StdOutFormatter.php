<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Logger\Handlers\StdOut;

use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use function ServiceBus\Common\jsonEncode;

/**
 *
 */
final class StdOutFormatter extends LineFormatter
{
    private const COLOR_TEMPLATES = [
        LogLevel::EMERGENCY => "\033[1;31m{level}\033[0m",
        LogLevel::ALERT     => "\e[1;31m{level}\e[0m",
        LogLevel::CRITICAL  => "\e[1;31m{level}\e[0m",
        LogLevel::ERROR     => "\e[1;31m{level}\e[0m",
        LogLevel::WARNING   => "\033[1;33m{level}\033[0m",
        LogLevel::NOTICE    => "\033[1;32m{level}\033[0m",
        LogLevel::INFO      => "\033[1;35m{level}\033[0m",
        LogLevel::DEBUG     => "\033[1;36m{level}\033[0m",
        'default'           => "\033[1m{level}\033[0m"
    ];

    public function __construct(string $format = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\r\n")
    {
        parent::__construct($format);
    }

    /**
     * @inheritDoc
     */
    public function format(array $record): string
    {
        $record['level_name'] = $this->ansifyLevel((string) $record['level_name']);
        $record['channel']    = "\033[1m{$record['channel']}\033[0m";

        return parent::format($record);
    }

    /**
     * @inheritDoc
     */
    protected function toJson($data, bool $ignoreErrors = false): string
    {
        if (\is_array($data))
        {
            return jsonEncode($data);
        }

        // @codeCoverageIgnoreStart
        return '';
        // @codeCoverageIgnoreEnd
    }

    private function ansifyLevel(string $level): string
    {
        $level = \strtolower($level);

        $template = self::COLOR_TEMPLATES[$level] ?? self::COLOR_TEMPLATES['default'];

        return \str_replace('{level}', $level, $template);
    }
}
