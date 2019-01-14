<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Infrastructure\Logger\Handlers\Graylog;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

/**
 *
 */
final class GelfFormatter extends NormalizerFormatter
{
    private const GRAYLOG_VERSION    = 1.0;
    private const DEFAULT_MAX_LENGTH = 32766;

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
     * The name of the system for the Gelf log message
     *
     * @var string
     */
    private $systemName;

    /**
     * Max length per field
     *
     * @var int
     */
    private $maxLength;

    /**
     * @param int $maxLength
     */
    public function __construct(int $maxLength = self::DEFAULT_MAX_LENGTH)
    {
        parent::__construct('U.u');

        $this->systemName = \gethostname();
        $this->maxLength  = $maxLength;
    }

    /**
     * @inheritdoc
     */
    public function format(array $record): array
    {
        /** @var array{datetime:int, message:string, level:int, channel:string, extra:array|null, context:array|null} $normalizedRecord */
        $normalizedRecord = parent::format($record);

        /** @var array{
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
            'line'          => $normalizedRecord['extra']['line'] ?? null
        ];

        unset($normalizedRecord['extra']['file'], $normalizedRecord['extra']['line']);

        /** @var array<string, string|int|float|array|null> $extraData */
        $extraData = $normalizedRecord['extra'] ?? [];

        /** @var array<string, string|int|float|array|null> $contextData */
        $contextData = $normalizedRecord['context'] ?? [];

        $formatted = $this->formatMessage((string) $normalizedRecord['message'], $formatted);
        $formatted = $this->formatAdditionalData($extraData, $formatted);
        $formatted = $this->formatAdditionalData($contextData, $formatted);

        return \array_filter($formatted);
    }

    /**
     * Format message data
     *
     * @param string $message
     * @param array  $formatted
     *
     * @return array
     */
    private function formatMessage(string $message, array $formatted): array
    {
        $len = 200 + \strlen($message) + \strlen($this->systemName);

        if($len > $this->maxLength)
        {
            $formatted['short_message'] = \substr($message, 0, $this->maxLength);
            $formatted['full_message']  = $message;
        }

        return $formatted;
    }

    /**
     * Format extra\context data
     *
     * @param array<string, string|int|float|array|null> $collection
     * @param array                                      $formatted
     *
     * @return array
     */
    private function formatAdditionalData(array $collection, array $formatted): array
    {
        /**
         * @var string                             $key
         * @var string|int|float|array|object|null $value
         */
        foreach($collection as $key => $value)
        {
            $value = $this->formatValue($key, $value);

            if(null === $value)
            {
                continue;
            }

            $len = \strlen($key . $value);

            if(true === \is_string($value) && $len > $this->maxLength)
            {
                $formatted[$key] = \substr($value, 0, $this->maxLength);

                continue;
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }

    /**
     * @param string                             $key
     * @param string|int|float|array|object|null $value
     *
     * @return string|int|float|null
     *
     * @throws \LogicException Invalid type
     */
    private function formatValue(string $key, $value)
    {
        if(null === $value || true === \is_scalar($value))
        {
            return $value;
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if(true === \is_array($value) || true === \is_object($value))
        {
            return $this->toJson($value);
        }

        throw new \LogicException(
            \sprintf('Invalid "%s" field value type: "%s"', $key, \gettype($value))
        );
    }
}
