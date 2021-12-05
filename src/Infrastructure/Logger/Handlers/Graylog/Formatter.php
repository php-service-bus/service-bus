<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Logger\Handlers\Graylog;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

/**
 * Log entry formatter.
 */
final class Formatter extends NormalizerFormatter
{
    private const GRAYLOG_VERSION = 1.0;

    private const DEFAULT_MAX_LENGTH = 5000;

    /**
     * Translates Monolog log levels to Graylog2 log priorities.
     */
    private const LEVEL_RELATIONS = [
        Logger::DEBUG     => 7,
        Logger::INFO      => 6,
        Logger::NOTICE    => 5,
        Logger::WARNING   => 4,
        Logger::ERROR     => 3,
        Logger::CRITICAL  => 2,
        Logger::ALERT     => 1,
        Logger::EMERGENCY => 0,
    ];

    /**
     * The name of the system for the Gelf log message.
     *
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $systemName;

    /**
     * Max length per field.
     *
     * @psalm-var positive-int
     *
     * @var int
     */
    private $maxLength;

    /**
     * @psalm-param non-empty-string|null $systemName
     * @psalm-param positive-int|null $maxLength
     */
    public function __construct(?string $systemName = null, ?int $maxLength = null)
    {
        parent::__construct('U.u');

        $hostName = $systemName ?? \gethostname();
        $hostName = $hostName !== false && $hostName !== '' ? $hostName : 'n/d';

        $this->systemName = $hostName;
        $this->maxLength  = $maxLength ?? self::DEFAULT_MAX_LENGTH;
    }

    /**
     * @throws \RuntimeException if encoding fails and errors are not ignored
     */
    public function format(array $record): array
    {
        /**
         * @psalm-var array{
         *     datetime:int,
         *     message:string,
         *     level:int,
         *     channel:string|null,
         *     extra:array|null,
         *     context:array|null
         * } $normalizedRecord
         */
        $normalizedRecord = parent::format($record);

        /** @var array $extraData */
        $extraData = $normalizedRecord['extra'] ?? [];

        /**
         * @psalm-var array{
         *    version:float|int,
         *    host:string,
         *    timestamp:int,
         *    short_message:string,
         *    level:int,
         *    facility:string,
         *    file:string|null,
         *    line:int|null,
         *    extra:array|null
         * } $formatted
         */

        $formatted = [
            'version'       => self::GRAYLOG_VERSION,
            'host'          => $this->systemName,
            'timestamp'     => $normalizedRecord['datetime'],
            'short_message' => $normalizedRecord['message'],
            'level'         => self::LEVEL_RELATIONS[$normalizedRecord['level']],
            'facility'      => $normalizedRecord['channel'] ?? null,
            'file'          => $extraData['file'] ?? null,
            'line'          => $extraData['line'] ?? null,
        ];

        unset($normalizedRecord['extra']);

        /** @psalm-var array<string, string|int|float|array|null> $contextData */
        $contextData = $normalizedRecord['context'] ?? [];
        $formatted   = $this->formatMessage($normalizedRecord['message'], $formatted);
        $formatted   = $this->formatAdditionalData($extraData, $formatted);
        $formatted   = $this->formatAdditionalData($contextData, $formatted);

        return \array_filter($formatted);
    }

    /**
     * Format message data.
     */
    private function formatMessage(string $message, array $formatted): array
    {
        $len = 200 + \strlen($message) + \strlen($this->systemName);

        if ($len > $this->maxLength)
        {
            $formatted['short_message'] = \substr($message, 0, $this->maxLength);
            $formatted['full_message']  = $message;
        }

        return $formatted;
    }

    /**
     * Format extra\context data.
     *
     * @psalm-param array $collection
     *
     * @throws \RuntimeException if encoding fails and errors are not ignored
     */
    private function formatAdditionalData(array $collection, array $formatted): array
    {
        /**
         * @psalm-var string                             $key
         * @psalm-var string|int|float|array|object|null $value
         */
        foreach ($collection as $key => $value)
        {
            $value = $this->formatValue($value);

            if (null === $value)
            {
                continue;
            }

            /** @noinspection UnnecessaryCastingInspection */
            $len = \strlen($key . (string) $value);

            if (\is_string($value) && $len > $this->maxLength)
            {
                $formatted[$key] = \substr($value, 0, $this->maxLength);

                continue;
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }

    /**
     * @throws \RuntimeException if encoding fails and errors are not ignored
     */
    private function formatValue(array|float|int|object|string|null $value): float|int|string|null
    {
        if ($value === null || \is_scalar($value))
        {
            return $value;
        }

        return $this->toJson($value);
    }
}
