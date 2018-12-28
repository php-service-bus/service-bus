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
     * A prefix for 'context' fields from the Monolog record (optional)
     *
     * @var string|null
     */
    private $contextPrefix;

    /**
     * Max length per field
     *
     * @var int
     */
    private $maxLength;

    /**
     * @param string $contextPrefix
     * @param int    $maxLength
     */
    public function __construct(string $contextPrefix = 'ctxt_', int $maxLength = self::DEFAULT_MAX_LENGTH)
    {
        parent::__construct('U.u');

        $this->systemName    = \gethostname();
        $this->contextPrefix = $contextPrefix;
        $this->maxLength     = $maxLength;
    }

    /**
     * @inheritdoc
     */
    public function format(array $record): array
    {
        $normalizedRecord = parent::format($record);

        $formatted = [
            'version'       => 1.0,
            'timestamp'     => $normalizedRecord['datetime'],
            'short_message' => (string) $normalizedRecord['message'],
            'host'          => $this->systemName,
            'level'         => self::LEVEL_RELATIONS[(int) $normalizedRecord['level']]
        ];

        $len = 200 + \strlen((string) $normalizedRecord['message']) + \strlen($this->systemName);

        if($len > $this->maxLength)
        {
            $formatted['short_message'] = \substr($normalizedRecord['message'], 0, $this->maxLength);
            $formatted['full_message']  = $normalizedRecord['message'];
        }

        if(true === isset($normalizedRecord['channel']))
        {
            $formatted['facility'] = $normalizedRecord['channel'];
        }

        if(true === isset($normalizedRecord['extra']['line']))
        {
            $formatted['line'] = $normalizedRecord['extra']['line'];

            unset($normalizedRecord['extra']['line']);
        }

        if(true === isset($normalizedRecord['extra']['file']))
        {
            $formatted['file'] = $normalizedRecord['extra']['file'];
            unset($normalizedRecord['extra']['file']);
        }

        foreach($normalizedRecord['extra'] as $key => $val)
        {
            $val = \is_scalar($val) || null === $val ? $val : $this->toJson($val);
            $len = \strlen($key . $val);

            if($len > $this->maxLength)
            {
                $formatted[$key] = \substr($val, 0, $this->maxLength);

                break;
            }

            $formatted[$key] = $val;
        }

        foreach($normalizedRecord['context'] as $key => $val)
        {
            $val = \is_scalar($val) || null === $val ? $val : $this->toJson($val);
            $len = \strlen($this->contextPrefix . $key . $val);

            if($len > $this->maxLength)
            {
                $formatted[$key] = \substr($val, 0, $this->maxLength);
                break;
            }

            $formatted[$key] = $val;
        }

        //die(var_dump($formatted));

        return $formatted;
    }
}
