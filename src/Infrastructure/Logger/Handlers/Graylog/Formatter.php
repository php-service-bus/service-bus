<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
     */
    private string $systemName;

    /**
     * Max length per field.
     */
    private int $maxLength;

    public function __construct(?string $systemName = null, ?int $maxLength = null)
    {
        parent::__construct('U.u');

        $this->systemName = $systemName ?? (string) \gethostname();
        $this->maxLength  = $maxLength ?? self::DEFAULT_MAX_LENGTH;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if encoding fails and errors are not ignored
     */
    public function format(array $record): array
    {
        /** @var array{datetime:int, message:string, level:int, channel:string, extra:array|null, context:array|null} $normalizedRecord */
        $normalizedRecord = parent::format($record);

        /** @psalm-var array{
         *    version:float|int,
         *    host:string,
         *    timestamp:int,
         *    short_message:string,
         *    level:int,
         *    facility:string,
         *    file:string|null,
         *    line:int|null
         * } $formatted
         */
        $formatted = [
            'version'       => self::GRAYLOG_VERSION,
            'host'          => $this->systemName,
            'timestamp'     => $normalizedRecord['datetime'],
            'short_message' => (string) $normalizedRecord['message'],
            'level'         => self::LEVEL_RELATIONS[(int) $normalizedRecord['level']],
            'facility'      => $normalizedRecord['channel'] ?? null,
            'file'          => $normalizedRecord['extra']['file'] ?? null,
            'line'          => $normalizedRecord['extra']['line'] ?? null,
        ];

        unset($normalizedRecord['extra']['file'], $normalizedRecord['extra']['line']);

        /** @psalm-var array<string, string|int|float|array|null> $extraData */
        $extraData = $normalizedRecord['extra'] ?? [];

        /** @psalm-var array<string, string|int|float|array|null> $contextData */
        $contextData = $normalizedRecord['context'] ?? [];
        $formatted   = $this->formatMessage((string) $normalizedRecord['message'], $formatted);
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
     * @psalm-param array<string, string|int|float|array|null> $collection
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

            $len = \strlen($key . (string) $value);

            if (true === \is_string($value) && $len > $this->maxLength)
            {
                $formatted[$key] = \substr($value, 0, $this->maxLength);

                continue;
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }

    /**
     * @param array|float|int|object|string|null $value
     *
     * @throws \RuntimeException if encoding fails and errors are not ignored
     *
     * @return float|int|string|null
     */
    private function formatValue($value)
    {
        if (null === $value || true === \is_scalar($value))
        {
            return $value;
        }

        /** @noinspection UnnecessaryCastingInspection */
        return (string) $this->toJson($value);
    }
}
