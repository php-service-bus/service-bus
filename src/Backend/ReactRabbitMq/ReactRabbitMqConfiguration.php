<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Backend\ReactRabbitMq;

use Desperado\Domain\ParameterBag;
use Desperado\Framework\Backend\InvalidDaemonConfigurationException;

/**
 * Bunny client configuration data
 */
class ReactRabbitMqConfiguration
{
    private const DEFAULT_SCHEMA = 'amqp';
    private const DEFAULT_HOST = 'localhost';
    private const DEFAULT_PORT = 5672;
    private const DEFAULT_USERNAME = 'guest';
    private const DEFAULT_PASSWORD = 'guest';
    private const DEFAULT_TIMEOUT = 1;
    private const DEFAULT_VIRTUAL_HOST = '/';

    private const DEFAULT_QOS_PRE_FETCH_SIZE = 0;
    private const DEFAULT_QOS_PRE_FETCH_COUNT = 5;
    private const DEFAULT_QOS_GLOBAL = false;

    /**
     * Connection DSN parameters bag
     *
     * @var ParameterBag
     */
    private $connectionConfig;

    /**
     * QOS settings
     *
     * @var ParameterBag
     */
    private $qosConfig;

    /**
     * Connection DSN example: amqp://admin:admin@localhost:5672
     *
     * Qos settings example:
     *
     * [
     *      'pre_fetch_count'  => 1,
     *      'pre_fetch_size'   => 0,
     *      'global' => false
     * ]
     *
     * @param string $connectionDSN
     * @param array  $qosSettings
     *
     * @throws InvalidDaemonConfigurationException
     */
    public function __construct(string $connectionDSN, array $qosSettings)
    {
        $this->connectionConfig = new ParameterBag($this->extractConnectionParameters($connectionDSN));
        $this->qosConfig = new ParameterBag($this->extractQosConfiguration($qosSettings));
    }

    /**
     * Get connection configuration
     *
     * @return ParameterBag
     */
    public function getConnectionConfig(): ParameterBag
    {
        return $this->connectionConfig;
    }

    /**
     * Get QOS settings
     *
     * @return ParameterBag
     */
    public function getQosConfig(): ParameterBag
    {
        return $this->qosConfig;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return \sprintf(
            '%s://%s:%s@%s:%s?vhost=%s&timeout=%d',
            $this->connectionConfig->get('scheme'),
            $this->connectionConfig->get('user'),
            $this->connectionConfig->get('password'),
            $this->connectionConfig->get('host'),
            $this->connectionConfig->get('port'),
            $this->connectionConfig->get('vhost'),
            $this->connectionConfig->get('timeout')
        );
    }

    /**
     * Extract QOS config
     *
     * @param array $qosConfigData
     *
     * @return array
     */
    private function extractQosConfiguration(array $qosConfigData): array
    {
        $intFilter = function($value, string $field)
        {
            $value = (int) $value;

            if(0 > $value)
            {
                throw new InvalidDaemonConfigurationException(
                    \sprintf('"%s" value must be greater or equals than zero', $field)
                );
            }

            return $value;
        };

        $fetchSize = $qosConfigData['pre_fetch_size'] ?? self::DEFAULT_QOS_PRE_FETCH_SIZE;
        $fetchCount = $qosConfigData['pre_fetch_count'] ?? self::DEFAULT_QOS_PRE_FETCH_COUNT;
        $globalFlag = $qosConfigData['global'] ?? self::DEFAULT_QOS_GLOBAL;

        return [
            'pre_fetch_size'  => $intFilter($fetchSize, 'pre_fetch_size'),
            'pre_fetch_count' => $intFilter($fetchCount, 'pre_fetch_count'),
            'global'          => (bool) $globalFlag
        ];
    }

    /**
     * @param string $connectionDSN
     *
     * @return array
     */
    private function extractConnectionParameters(string $connectionDSN): array
    {
        $connectionParts = new ParameterBag($this->parseUrl($connectionDSN));
        $queryParts = new ParameterBag(
            $this->parseQuery(
                $connectionParts->get('query', '')
            )
        );

        return [
            'scheme'   => $connectionParts->getAsString('scheme', self::DEFAULT_SCHEMA),
            'host'     => $connectionParts->getAsString('host', self::DEFAULT_HOST),
            'port'     => $connectionParts->getAsInt('port', self::DEFAULT_PORT),
            'user'     => $connectionParts->getAsString('user', self::DEFAULT_USERNAME),
            'password' => $connectionParts->getAsString('pass', self::DEFAULT_PASSWORD),
            'timeout'  => $queryParts->getAsInt('timeout', self::DEFAULT_TIMEOUT),
            'vhost'    => $queryParts->getAsString('vhost', self::DEFAULT_VIRTUAL_HOST)
        ];
    }

    /**
     * Parse connection DSN parts
     *
     * @param string $url
     *
     * @return array
     *
     * @throws InvalidDaemonConfigurationException
     */
    private function parseUrl(string $url): array
    {
        if('' === $url)
        {
            throw new InvalidDaemonConfigurationException('Connection DSN can\'t be empty');
        }

        $parsedParts = \parse_url($url);

        if(false !== $parsedParts)
        {
            return $parsedParts;
        }

        throw new InvalidDaemonConfigurationException(
            \sprintf('Can\'t parse specified connection DSN (%s)', $url)
        );
    }

    /**
     * Parse url query parts
     *
     * @param string $query
     *
     * @return array
     */
    private function parseQuery(string $query): array
    {
        $output = [];

        \parse_str($query, $output);

        return $output;
    }
}
