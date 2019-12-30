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
use function Amp\Log\hasColorSupport;
use function ServiceBus\Common\jsonEncode;

/**
 * @codeCoverageIgnore
 */
final class StdOutFormatter extends LineFormatter
{
    /** @var bool */
    private $colorize;

    public function __construct()
    {
        parent::__construct("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\r\n");

        $this->colorize = hasColorSupport();
    }

    /**
     * @inheritDoc
     */
    public function format(array $record): string
    {
        if ($this->colorize === true)
        {
            $record['level_name'] = $this->ansifyLevel($record['level_name']);
            $record['channel']    = "\033[1m{$record['channel']}\033[0m";
        }

        return parent::format($record);
    }

    /**
     * @inheritDoc
     */
    protected function toJson($data, bool $ignoreErrors = false): string
    {
        if (\is_array($data) === true)
        {
            return jsonEncode($data);
        }

        return '';
    }

    private function ansifyLevel(string $level): string
    {
        $level = \strtolower($level);

        switch ($level)
        {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                /** bold + red */
                return "\033[1;31m{$level}\033[0m";
            case LogLevel::WARNING:
                /** bold + yellow */
                return "\033[1;33m{$level}\033[0m";
            case LogLevel::NOTICE:
                /** bold + green */
                return "\033[1;32m{$level}\033[0m";
            case LogLevel::INFO:
                /** bold + magenta */
                return "\033[1;35m{$level}\033[0m";
            case LogLevel::DEBUG:
                /** bold + cyan */
                return "\033[1;36m{$level}\033[0m";
            default:
                /** bold */
                return "\033[1m{$level}\033[0m";
        }
    }
}
